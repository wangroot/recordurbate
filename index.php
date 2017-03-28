<?php
require_once 'php/templates/pageHeader.php';
require_once 'php/config.php';
?>

<section class="container grid-960">
	<section class="columns">
		<div class="column col-12 text-center">
			<h1>Recordurbate</h1>
			<h5>The act of recording a Chaturbate livestream</h5>
		</div>
	</section>
	<div class="divider"></div>
	<section class="columns">
		<div class="column col-12">
			<form class="form-horizontal" action="cmd.php" method="get">
				<div class="form-group">
					<div class="col-8">
						<input type="text" class="form-input" name="name" placeholder="Username" required minlength="1">
					</div>
					<div class="col-1"></div>
					<div class="col-3">
						<button type="submit" name="action" value="Add" class="btn full btn-primary">Add Streamer</button>
					</div>
				</div>
			</form>
		</div>
	</section>
	<div class="divider"></div>
	<section class="columns">
		<div class="column col-12">
			<table class="table table-striped table-hover">
				<thead>
				<tr>
					<th></th>
					<th>Username</th>
					<th>Total Recordings</th>
					<th>Last Recording</th>
					<th>Commands</th>
				</tr>
				</thead>
				<tbody>
				<?php
					for($i = 0; $i < sizeof($streamers); $i++)
					{
						echo "<tr>";

						if($streamers[$i]["recording"])
						{
							echo "<td class=\"center\"><i class=\"material-icons\">videocam</i></td>";
						} else
						{
							echo "<td></td>";
						}

						echo "<td>" . $streamers[$i]["name"] . "</td>";
						echo "<td>" . $streamers[$i]["total"] . "</td>";
						echo "<td>" . $streamers[$i]["last"] . "</td>";
						echo "<td><form method=\"get\" action=\"cmd.php\"><input type=\"hidden\" name=\"name\" value=\"" . $streamers[$i]["name"]. "\"><section class=\"columns\">";
						if($streamers[$i]["enabled"])
						{
							echo "<div class=\"column col-6\"><input type=\"submit\" class=\"btn full btn-primary\" name=\"action\" value=\"Disable\"></div>";
						} else
						{
							echo "<div class=\"column col-6\"><input type=\"submit\" class=\"btn full btn-primary\" name=\"action\" value=\"Enable\"></div>";
						}
						echo " <div class=\"column col-6\"><input type=\"submit\" class=\"btn full btn-danger\" name=\"action\" value='Delete'></div></section></form></td></tr>";
					}
				?>
				</tbody>
			</table>
		</div>
	</section>
</section>

<?php
require_once 'php/templates/pageFooter.php';
?>