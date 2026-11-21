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
	<div style="font-weight: bold">For Delivery of goods please apply to</div>
	<div><select id="agent-select" style="width: 100%"></select>
	</div>
	<div>
		<textarea name="for-delivery" id="for-delivery" style="min-height: 95px; max-height: 95px; min-width: 385px; max-width: 385px; padding: 5px; font-size: .8em; text-transform: uppercase; margin-top: 5px"><?php echo $nama_agent. htmlentities("\n"). $add_agent ; ?></textarea>
	</div>
	<div style="clear: both; height: 5px"></div>
<hr>
<button style="float: right; width: 80; height: 30" onclick="update_agent()">UPDATE</button>

</div>

<script type="text/javascript">
  $(document).ready(function() {
    $('#agent-select').select2({
      placeholder: 'Pilih Agent Company',
      ajax: {
        url: "<?php echo site_url('afg_c_bill_of_ladding/load_dropdown_data')?>", // sesuaikan dengan route kamu
        dataType: 'json',
        delay: 250,
        processResults: function(data) {
          return {
            results: $.map(data, function(item) {
              return {
                id: item.id,
				text: item.text, // agent_company_code sebagai label
				name: item.name,
                address: item.address
              };
            })
          };
        }
      }
    });

    $('#agent-select').on('select2:select', function(e) {
      let data = e.params.data;
      let content = data.name + "\n" + data.address;
      $('#for-delivery').val(content);
    });
  });

function update_agent(){
	var rowid = $('#id_form').val();
	$.ajax({
		type: "POST", 
		url : "<?php echo site_url('afg_c_bill_of_ladding/update_agent_draft')?>",
		data: {
			for_delivery : $('#for-delivery').val(),rowid : rowid
		},
		success:function(XMLHttpRequest)
		{
			$( "#agent-insert" ).dialog("close");
		}
	})
}
</script>

