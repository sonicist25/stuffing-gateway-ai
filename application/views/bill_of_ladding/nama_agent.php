<?php 
	$agent = $this->agent_company_model->getAgentByCode($code);

	if($agent != 0 ) {
		$rmks_agent = $agent['agent_company_remark'];
		$nama_agent = $agent['agent_company_name'];
		$add_agent = $agent['agent_company_address'];
		$special_instruction = $agent['special_instruction'];
	}
	else 
	{
		$rmks_agent = '';
		$nama_agent = '';
		$add_agent = '';
		$special_instruction = '';
	}
?>
<div class="form_bck">
	<div style="font-weight: bold">Remaks Agent</div>
	<div>
		<textarea name="remarks-agent" id="remarks-agent" style="min-height: 65px; max-height: 65px; min-width: 385px; max-width: 385px; padding: 5px; font-size: .8em; text-transform: uppercase; margin-top: 5px"><?php echo $rmks_agent ?></textarea>
	</div>
	<div style="clear: both; height: 5px"></div>
	<div style="font-weight: bold">For Delivery of goods please apply to</div>
	<div>
		<textarea name="for-delivery" id="for-delivery" style="min-height: 95px; max-height: 95px; min-width: 385px; max-width: 385px; padding: 5px; font-size: .8em; text-transform: uppercase; margin-top: 5px"><?php echo $nama_agent. htmlentities("\n"). $add_agent ; ?></textarea>
	</div>
	<div style="clear: both; height: 5px"></div>
	<div style="font-weight: bold">Special Instruction</div>
	<div>
		<textarea name="special-instruction" id="special-instruction" style="min-height: 95px; max-height: 95px; min-width: 385px; max-width: 385px; padding: 5px; font-size: .8em; text-transform: uppercase; margin-top: 5px"><?php echo $special_instruction ?></textarea>
	</div>

<hr>
<button style="float: right; width: 80; height: 30" onclick="update_agent()">UPDATE</button>

</div>

<script type="text/javascript">
$(document).ready(function(){
	/*
	var rmks_agent = "<?php echo $rmks_agent ?>";
	var nama_agent = "<?php echo $nama_agent ?>";
	var add_agent = "<?php echo $add_agent ?>";
	var special_instruction = "<?php echo $special_instruction ?>";
	$('#remarks-agent').html(rmks_agent);
	$('#for-delivery').html(nama_agent+'\n'+add_agent);
	$('#special-instruction').html(special_instruction);
	*/
})

function update_agent(){
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_bill_of_ladding/update_agent')?>",
		data: {
			agent_company_remark : $('#remarks-agent').val(),
			for_delivery : $('#for-delivery').val(),
			special_instruction : $('#special-instruction').val(),
			agent_company_code : "<?= $code ?>",
		},
		success:function(XMLHttpRequest)
		{
			$( "#agent-insert" ).dialog("close");
		}
	})
}
</script>

