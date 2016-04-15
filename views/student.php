<?php

$html = <<< EOT
<style>
.clicker {
	margin: auto;
	width:60%;
}

.question {
	margin: auto;
	border: 3px solid #73AD21;
	margin-bottom: 5px;
	padding-bottom: 20px;
	margin-top: 5px;
	padding-top: 20px;
	text-align: center;
}

.multiple_choice {
	/*text-align: center;*/
}

.answer {
	width:100%;
}
</style>

<div class="clicker">
	<div class='question'>
		
	</div>

	<div class="choices">

	</div>
</div>



<div class="templates" style="display:none">
	<form class='multiple_choice' id="">
		<div class="form-group answer">
			<button class="btn btn-default" id="" type="button">A</button>
		</div>
	</form>
</div>

<script>
	document.write('<script src="http://' + (location.host || 'localhost').split(':')[0] + ':35729/livereload.js?snipver=1"></' + 'script>')
</script>

<script>
$("form button.answer").click(function(event) {
	var target = $(event.target);
	console.log(target);
});
</script>
EOT;

echo($html);

