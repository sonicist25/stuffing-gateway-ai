<?php
Class Music {
	
	function music() {
		$this->CI =& get_instance();
		echo 
		'<!--Music-->
		<link rel="stylesheet" type="text/css" href="'. base_url('music/jplayer.css').'" media="screen" />
		<script type="text/javascript" SRC="'. base_url('music/jquery-1.4.2.min.js').'"></script>
		<script type="text/javascript" SRC="'. base_url('music/jquery.jplayer.min.js').'"></script>
		<div class="music">
			<div id="jquery_jplayer" class="jp-jplayer"></div>
			<!-- Disi sound css nya -->
			<div id="jp_container" class="jp-audio">
				<ul class="jp-controls">
					<li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute"></a></li>
					<li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute"></a></li>
					<li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume"></a></li>
				</ul>            
				<div class="jp-txt">SOUND CONTROL</div>            
				<div class="jp-volume-bar"><div class="jp-volume-bar-value"></div></div>
			</div>
			<script>
			$(window).load(function() {	
				var qwe = setTimeout(function () {$("#jquery_jplayer").jPlayer("play");}, 2000);
			})
			$("#jquery_jplayer").jPlayer({
				ready: function () {
					$(this).jPlayer("setMedia", {
						mp3:"'. base_url('music/music.mp3').'"
					});
					//$(this).jPlayer("play");
				},
				repeat: function(event) { // Override the default jPlayer repeat event handler				
					$(this).bind($.jPlayer.event.ended + ".jPlayer.jPlayerRepeat", function() {
						$(this).jPlayer("play");
					});			
				},
				swfPath: "'. base_url('music').'",
				cssSelectorAncestor: "#jp_container",
				supplied: "mp3",
				wmode: "window"
			});
			</script>
		</div>'; 
	}
}