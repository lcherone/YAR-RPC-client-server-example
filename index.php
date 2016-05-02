<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

$table  = 'default';
$vars = [];

function get_machine_id() {
	if (file_exists('./machine-id')) {
		return file_get_contents('./machine-id');
	}
	if (file_exists('/var/lib/dbus/machine-id')) {
		$id = trim(`cat /var/lib/dbus/machine-id`);
		file_put_contents('./machine-id', $id);
		return $id;
	}
	if (file_exists('/etc/machine-id')) {
		$id = trim(`cat /etc/machine-id`);
		file_put_contents('./machine-id', $id);
		return $id;
	}
	$id = sha1(uniqid(true));
	file_put_contents('./machine-id', $id);
	return $id;
}

/**
 * Attempts to get originating IP address of user,
 * Spoofable, but in future we may want to use load balancing.
 */
function getIPAddress() {
	if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}

try {
	
	$host = new yar_client("http://api.oss.tools/hosts.php");

	$nodes = $host->findAll('nodes');
	//find self
	$peer = $host->findAll('nodes', 'machine_id = ?', [get_machine_id()]);
	

	// else add self to network
	if (empty($peer)) {
		$host->create(
			'nodes', [
				'machine_id' => get_machine_id(),
				'ip' => $_SERVER['SERVER_ADDR'],
				'hostname' => $_SERVER['HTTP_HOST'],
				'added' => date_create()->format('Y-m-d h:i:s'),
				'updated' => date_create()->format('Y-m-d h:i:s'),
			]
		);
		exit(header('Location: ./index.php'));
	}

	if (!empty($_SESSION['host'])) {
		$api = new yar_client('http://'.$_SESSION['host']['hostname'].'/server.php');
	
		//get all tables
		$tables = $api->inspect();
	}

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (in_array('action', array_keys($_POST))) {
			
			$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
			$id     = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
			$table  = filter_input(INPUT_POST, 'table', FILTER_SANITIZE_STRING);

			switch ($action) {
				/**
				 * 
				 */
				case "create": {
					if (isset($_POST['column']) && is_array($_POST['column'])) {
						
						$table = (!empty($_POST['table']) ? preg_replace("/[^a-z_]/", '', strtolower(str_replace(' ', '_', $_POST['table']))) : $table);
						
						if (empty($table)) {
							$error = 'After removing invalid chars, it seems your table name is invalid';
							break;
						}
						
						foreach ($_POST['column'] as $col) {
							$_POST[preg_replace("/[^a-z_]/", '', strtolower(str_replace(' ', '_', $col)))] = '';
						}
					}

					unset($_POST['action']);
					unset($_POST['id']);
					unset($_POST['table']);
					unset($_POST['column']);
					
					$_POST['added']   = date_create()->format('Y-m-d h:i:s');
					$_POST['updated'] = date_create()->format('Y-m-d h:i:s');

					$api->create(
						strtolower($table), $_POST
					);
					exit(header('Location: /'));
				} break;

				/**
				 * 
				 */
				case "edit":
				case "update": {
					$_POST['updated'] = date_create()->format('Y-m-d h:i:s');
					
					$api->update(strtolower($table), (int) $id, $_POST);
					exit(header('Location: /'));
				} break;    
			}
		}
	} else {
		// modals
		if (in_array('do', array_keys($_GET))) {
			switch (filter_input(INPUT_GET, 'do', FILTER_SANITIZE_STRING)) {
				/**
				 * 
				 */
				case "modal": { 
					$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
					$table = filter_input(INPUT_GET, 'table', FILTER_SANITIZE_STRING);
					$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
					$columns = $api->inspect($table);

					$row = [];
					if ($action == 'update' && !empty($id)) {
						$row = $api->load($table, (int) $id);
					}

					if ($action == 'create' || $action == 'update') { ?>
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								<h4 class="modal-title"><?= (!empty($row['id']) ? 'Update' : 'Create') ?> <?= $table ?> entry</h4>
							</div>
							<div class="modal-body clearfix">
								<div class="col-xs-12">
									<form role="form" name="create-form" id="create-form" action="" method="POST" class="form-horizontal">
										<input type="hidden" name="action" value="<?= $action ?>">
										<input type="hidden" name="table" value="<?= $table ?>">
										<?= (!empty($row['id']) ? '<input type="hidden" name="id" value="'.$row['id'].'">' : null) ?>
										<?php foreach ($columns as $column => $type): if (in_array($column, ['id', 'updated', 'added'])) continue; ?>
										<div class="form-group">
											<label for="_<?= $column ?>" class="control-label"><?= ucfirst(str_replace('_', ' ', $column)) ?></label>
											<input type="text" class="form-control" name="<?= $column ?>" id="_<?= $column ?>" value="<?= (!empty($row[$column]) ? htmlentities($row[$column]) : null) ?>" placeholder="Enter <?= strtolower(str_replace('_', ' ', $column)) ?>..."/>
										</div>
										<?php endforeach ?>
										<div class="row modal-footer">
											<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
											<button type="submit" class="btn btn-primary">Save</button>
										</div>
									</form>
								</div>
							</div>
						</div>
						<?php
					}
					exit;
				} break;
				
				/**
				 * 
				 */
				case "remove":
				case "delete": {
					$table = filter_input(INPUT_GET, 'table', FILTER_SANITIZE_STRING);
					$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
					
					$api->delete($table, (int) $id);
				} break;
				
				/**
				 * 
				 */
				case "connect":  {
					$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
					$row = $host->load('nodes', (int) $id);
					
					$_SESSION['host'] = $row;
					exit(header('Location: /'));
				} break;				
				
				/**
				 * 
				 */
				case "disconnect":  {
					$_SESSION['host'] = [];
					exit(header('Location: /'));
				} break;
				
				/**
				 * 
				 */
				case "delete_server":  {
					$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
					$host->delete('nodes', (int) $id);
					
					exit(header('Location: /'));
				} break;
				
				/**
				 * 
				 */
				case "delete_column":  {
					$table = filter_input(INPUT_GET, 'table', FILTER_SANITIZE_STRING);
					$column = filter_input(INPUT_GET, 'column', FILTER_SANITIZE_STRING);
					
					//make safe
					$table = preg_replace("/[^a-z_]/", '', strtolower(str_replace(' ', '_', $table)));
					$column = preg_replace("/[^a-z_]/", '', strtolower(str_replace(' ', '_', $column)));
					
					//workaround no drop column in sqlite
					$tmp = $api->findAll($table);

					$api->exec('DROP TABLE `'.$table.'`');
					
					foreach ($tmp as $row) {
						unset($row['id']);
						unset($row[$column]);
						$api->create(
							$table, $row
						);
					}

					exit(header('Location: /'));
				} break;
				
				/**
				 * 
				 */
				case "delete_table":  {
					$table = filter_input(INPUT_GET, 'table', FILTER_SANITIZE_STRING);

					//make safe
					$table = preg_replace("/[^a-z_]/", '', strtolower(str_replace(' ', '_', $table)));

					$api->exec('DROP TABLE `'.$table.'`');

					exit(header('Location: /'));
				} break;
			}
		}
	}

	$error = '';
} catch (Exception $e) {
	$error = '<span style="color:red;font-weight:bold">Oops! '.$e->getMessage().'</span>';
}

//print_r($_SESSION);

//$host->wipe('nodes');
//$api->wipe('contacts');
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="Lawrence Cherone">

		<title></title>
		
		<!-- Font-awesome CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">

		<!-- Bootstrap Core CSS -->
		<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">

		<!-- CSS - Taken and tweeked from the extentions html output -->
		<style>
			body {
				background:#f8f8f8;
				color:#333;
				font:14px/20px HelveticaNeue-Light, "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
				margin:0
			}

			h1,h2,pre{
				margin:0;
				padding:0
			}

			h1 {
				background:#99c;
				border-bottom:4px solid #669;
				box-shadow:0 1px 4px #bbb;
				color:#222;
				font:bold 28px Verdana,Arial;
				padding:12px 5px
			}

			h2 {
				border-bottom:1px solid #ddd;
				cursor:pointer;
				font:normal 18px/20px serif;
				margin:20px 10px 0;
				padding:5px 0 8px
			}
			
			h2 a.btn {
				font:12px HelveticaNeue-Light, "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
				margin-top:-10px;
			}

			p {
				color:#555
			}

			.api-info {
				margin-left:20px;
				padding:10px 0
			}
			
			.tab-content {
				border-left: 1px solid #ddd;
				border-right: 1px solid #ddd;
				border-bottom: 1px solid #ddd;
				padding: 10px;
				background:#fff
			}
			
			.nav-tabs {
				margin-bottom: 0;
			}
		</style>

		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>

	<body>
		<h1>Yar Client <?php if (!empty($_SESSION['host']['machine_id'])): ?><small class="pull-right" style="margin-top:7px;margin-right:7px;"><a href="http://<?= $_SESSION['host']['hostname'] ?>/server.php"><span style="color:#e7e7e7;"><?= $_SESSION['host']['machine_id'] ?></span></a></small><?php endif ?></h1>

		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<?= $error ?>
					<h2>
						Servers List
					</h2>
					<p class="api-info">
						Servers are instances of this script, each with there own database. To add a server or for more information about this sunday project <a href="https://bitbucket.org/lcherone/yar-rpc-client-server-example" target="_blank">click here</a>.
					</p>
					<div class="col-md-12">
						<table class="table table-striped table-hover">
							<thead>
								<tr>
									<th>Machine ID</th>
									<th>IP Address</th>
									<th>Hostname</th>
									<th>Added</th>
									<th>Updated</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($nodes as $row): ?>
								<tr>
									<!--<td><a href="#ajax-modal" data-url="/?do=modal&action=update&table=peers&id=<?= (int) $row['id'] ?>" data-size="modal-md" class="ajax-model" data-toggle="modal"><?= htmlentities($row['machine_id']) ?></a></td>-->
									<td><?= htmlentities($row['machine_id']) ?></td>
									<td><?= htmlentities($row['ip']) ?></td>
									<td><?= htmlentities($row['hostname']) ?></td>
									<td><?= htmlentities($row['added']) ?></td>
									<td><?= htmlentities($row['updated']) ?></td>
									<td>
										<div class="btn-group btn-group-xs">
											<a href="/?do=delete_server&id=<?= (int) $row['id'] ?>" class="btn btn-danger">Delete</a>
											<a href="/?do=connect&id=<?= (int) $row['id'] ?>" class="btn btn-success">Connect</a>
											<a href="/?do=disconnect&id=<?= (int) $row['id'] ?>" class="btn btn-warning">Disconnect</a>
										</div>
									</td>
								</tr>
								<?php endforeach ?>
							</tbody>
						</table>
					</div>
				</div>			
				<?php if (!empty($_SESSION['host'])): ?>
				<div class="col-xs-12">
					<?= $error ?>
					<h2>
						Tables
					</h2>
					<p class="api-info">
						The following data is stored and retrieved from 
						<code><a href="http://<?= $_SESSION['host']['hostname'] ?>/server.php"><?= $_SESSION['host']['hostname'] ?></a> (<?= $_SESSION['host']['ip'] ?>)</code> through 
						<code><a href="http://<?= $peer[0]['hostname'] ?>/server.php"><?= $peer[0]['hostname'] ?></a> (<?= $peer[0]['ip'] ?>)</code> 
						using the RPC <a href="http://php.net/manual/en/class.yar-client.php" target="_blank"><code>Yar_Client</code></a>.
					</p>
					<div class="col-xs-12">
						<ul class="nav nav-tabs" id="myTab">
							<?php $i=0; foreach ($tables as $table): ?>
							<li class="<?= (($i == 0) ? ' active' : null) ?>">
								<a data-target="#tab-<?= $table ?>" data-toggle="tab">
									<?= str_replace('_', ' ', ucfirst($table)) ?> <span onclick="window.location.href = '?do=delete_table&table=<?= $table ?>'" style="cursor:pointer"><i class="fa fa-times text-danger"></i></a></span>
								</a>
							</li>
							<?php $i++; endforeach ?>
							<li class="pull-right"><a data-target="#tab-new-table" data-toggle="tab"><i class="fa fa-plus text-success"></i> Add Table</a></li>
						</ul>
						<div class="tab-content">
							<?php if (empty($tables)): ?>
							<p>There are currently no data on this server.</p>
							<?php endif ?>
							
							<div class="tab-pane" id="tab-new-table">
								<form role="form" name="create-table-form" id="create-table-form" action="" method="POST" class="form-horizontal" style="margin:0px 20px">
									<input type="hidden" name="action" value="create">
									<input type="hidden" name="table" value="<?= $table ?>">
									<div class="form-group">
										<label for="table" class="control-label">Table Name <small>(<span class="text-danger">All but a-z and _ is stripped.</span>)</small></label>
										<input type="text" class="form-control" name="table" id="table" value="<?= (!empty($row['table']) ? htmlentities($row['table']) : null) ?>" placeholder="enter name">
									</div>
									<div class="input-multi">
										<div class="form-group">
											<label>Columns/s <small>(<span class="text-danger">All but a-z and _ is stripped.</span>)</small></label>
											<div class="input-group">
												<input type="text" name="column[]" class="form-control">
												<span class="input-group-btn">
													<a href="javascript:void(0)" class="btn btn-success add_row" type="button"><i class="fa fa-plus"></i></a>
												</span>
											</div>
											<span class="help-block hidden"></span>
										</div>
									</div>
									<div class="row modal-footer">
										<button type="submit" class="btn btn-primary">Save</button>
									</div>
								</form>
							</div>
							<?php $i=0; foreach ($tables as $table): ?>
							<?php 
								//query server
								$result = $api->findAll($table);
							?>
							<div class="tab-pane<?= (($i == 0) ? ' active' : null) ?>" id="tab-<?= $table ?>">
								<a href="#ajax-modal" data-url="/?do=modal&action=create&table=<?= $table ?>" data-size="modal-md" class="ajax-model btn btn-xs btn-link pull-right" role="button" data-toggle="modal"><i class="fa fa-plus text-success"></i> Add Row</a>
								<?php if (empty($result[0])): ?>
								<p class="api-info text-danger">No records were found in the <?= $table ?> table.</p>
								<?php else: ?>
								<table class="table table-striped table-hover">
									<thead>
										<tr>
											<?php foreach ($result[0] as $column => $value): ?>
											<th<?php if (in_array($column, ['id', 'added', 'updated'])): ?> class="col-xs-1"<?php endif ?>><?= str_replace('_', ' ', ucfirst($column)) ?><?php if (!in_array($column, ['id', 'added', 'updated'])): ?> <a href="?do=delete_column&table=<?= $table ?>&column=<?= $column ?>"><i class="fa fa-times text-danger"></i></a><?php endif ?></th>
											<?php endforeach ?>
											<th class="col-xs-1"></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($result as $row): ?>
										<tr>
											<?php foreach ($result[0] as $column => $value): ?>
											<td<?php if (in_array($column, ['id', 'added', 'updated'])): ?> style="white-space: nowrap;"<?php endif ?>><?= $row[$column] ?></td>
											<?php endforeach ?>
											<td>
												<a href="/?do=delete&table=<?= $table ?>&id=<?= (int) $row['id'] ?>">Delete</a>
											</td>
										</tr>
										<?php endforeach ?>
									</tbody>
								</table>
								<?php endif ?>
							</div>
							<?php $i++; endforeach ?>
						</div>
					</div>
				</div>
				<?php endif ?>
			</div>
		</div>
		<div id="ajax-modal" class="modal fade">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title">Please wait</h4>
					</div>
					<div class="modal-body">
						<p>Loading...</p>
					</div>
				</div>
			</div>
		</div>
		<!-- jQuery -->
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.2.3/jquery.min.js"></script>
		<!-- Bootstrap -->
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
		<script>
		$(function() {
			var rows = 0;
				/* add row */
				$(".input-multi").on('click', '.add_row', function() {
					var parent = $(this).closest('div.form-group');
					var newrow =
						'<div class="form-group">' +
						'   <div class="input-group">' +
						'       <input type="text" name="column[]" class="form-control">' +
						'       <span class="input-group-btn">' +
						'            <a href="javascript:void(0)" class="btn btn-danger delete_row" type="button"><i class="fa fa-times"></i></a>' +
						'       </span>' +
						'   </div>' +
						'   <span class="help-block hidden"></span>' +
						'</div>';
					parent.after(newrow);
					rows++;
				});
				$(".input-multi").on('click', '.delete_row', function() {
					$(this).closest('div.form-group').remove();
					rows--;
				});
			});
		
			jQuery(document).ready(function ($) {
				$('#tabs').tab();
			});
			/**
			 * AJAX modal event handler, so we can handle more
			 */
			$(document).on("click", ".ajax-model", function (event) {
				event.preventDefault();

				var dialog_size = $(this).data('size');

				var request = $.ajax({
					url: $(this).data('url'),
					method: "GET",
					dataType: "html"
				});

				request.done(function(data) {
					var modal = $('#ajax-modal .modal-content');
					var content = $('<div />').html(data);

					if (dialog_size == 'modal-lg') {
						modal.parent().removeClass('modal-sm modal-md modal-lg').addClass('modal-lg');
					} else if(dialog_size == 'modal-sm') {
						modal.parent().removeClass('modal-sm modal-md modal-lg').addClass('modal-sm');
					} else {
						modal.parent().removeClass('modal-sm modal-md modal-lg').addClass('modal-md');
					}

					modal.replaceWith(content);
				});

				request.fail(function(jqXHR, textStatus) {
					alert("Request failed: " + textStatus);
				});
			});
		</script>
	</body>
</html>
