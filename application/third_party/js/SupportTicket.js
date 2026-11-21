$(function() {
    // jqGrid initialization
    $("#ticketGrid").jqGrid({
        url: window.location.origin+'/SupportTicket/ajax_list',
        datatype: "json",
        mtype: 'GET',
          colModel: [
            { label: 'ID', name: 'id', key: true, width: 60, sorttype: 'int' },
            { label: 'Username', name: 'username', width: 200 },
            { label: 'Description', name: 'description', width: 300 },
            { label: 'Action', name: 'action', width: 300 },
            { label: 'Action Date', name: 'action_date', width: 300 },
            { label: 'Finish Date', name: 'finish_date', width: 300 },
            { label: 'Status', name: 'status', width: 100 },
            { label: 'By', name: 'oleh', width: 100 },
            { label: 'Created At', name: 'created_at', width: 140, sorttype: 'date' }
        ],
        pager: "#ticketPager",
        rowNum: 10,
        rowList: [10, 20, 50],
        sortname: 'id',
        sortorder: 'desc',
        viewrecords: true,
        height: 'auto',
        autowidth: true,
        caption: "Support Tickets",
        jsonReader: {
            root: "rows",
            page: "page",
            total: "total",
            records: "records",
            repeatitems: false,
            id: "id"
        },
        multiselect: true
    });

    // Resize grid on window resize
    $(window).on('resize', function(){
        var gridWidth = $('#grid_container').width();
        $('#ticketGrid').setGridWidth(gridWidth);
    }).trigger('resize');

    // Add buttons above grid
    $("#ticketGrid").navGrid("#ticketPager",
        { edit: false, add: false, del: false, search: true, refresh: false }
    );

    // Custom button for Add
    $("#ticketGrid").navButtonAdd("#ticketPager",{
        caption:"Add",
        buttonicon:"ui-icon-plus",
        onClickButton: function() {
            openTicketModal('Add');
        },
        position:"last"
    });

    // Custom button for Edit (only when one is selected)
    $("#ticketGrid").navButtonAdd("#ticketPager",{
        caption:"Edit",
        buttonicon:"ui-icon-pencil",
        onClickButton: function() {
            var selected = $("#ticketGrid").jqGrid('getGridParam','selarrrow');
            if (selected.length === 1) {
                var rowId = selected[0];
                editTicket(rowId);
            } else {
                alert('Please select exactly one ticket to edit.');
            }
        },
        position:"last"
    });

    // Custom button for Delete (multiple allowed)
    $("#ticketGrid").navButtonAdd("#ticketPager",{
        caption:"Delete",
        buttonicon:"ui-icon-trash",
        onClickButton: function() {
            var selected = $("#ticketGrid").jqGrid('getGridParam','selarrrow');
            if (selected.length === 0) {
                alert('Please select one or more tickets to delete.');
                return;
            }
            if(confirm('Delete selected ticket(s)?')) {
                deleteTickets(selected);
            }
        },
        position:"last"
    });

    // Open modal for Add/Edit
    function openTicketModal(action) {
        $('#ticketForm')[0].reset();
        $('#ticket_id').val('');
        $('#ticketModalLabel').text(action + ' Ticket');
        $('#ticketModal').modal('show');
    }

    // Load ticket data for editing
    function editTicket(id) {
        $.getJSON(window.location.origin+'/SupportTicket/ajax_list', { page: 1, rows: 1, sidx: 'id', sord: 'asc', search: '', _search: false }, function(data) {
            // Since ajax_list returns paged data, better call a separate API or adjust model to get one record
            // We'll do a simple ajax call to the model via new controller method to get one ticket for editing
            
            $.ajax({
                url: window.location.origin+'/SupportTicket/ajax_get',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(data) {
                    if (data) {
                        $('#ticket_id').val(data.id);
                        $('#ticket_title').val(data.title);
                        $('#ticket_description').val(data.description);
                        $('#ticket_status').val(data.status);
                        $('#ticketModalLabel').text('Edit Ticket');
                        $('#ticketModal').modal('show');
                    } else {
                        alert('Failed to retrieve ticket details.');
                    }
                },
                error: function() {
                    alert('Failed to retrieve ticket data.');
                }
            });
        });
    }

    // Handle form submit for Add/Edit
    $("#ticketForm").submit(function(e) {
        e.preventDefault();
        var id = $('#ticket_id').val();
        var url = id ? window.location.origin+'/SupportTicket/ajax_edit' : window.location.origin+'/SupportTicket/ajax_add';
        $.ajax({
            url: url,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#ticketModal').modal('hide');
                    $("#ticketGrid").trigger('reloadGrid');
                } else {
                    alert('Failed to save ticket.');
                }
            },
            error: function() {
                alert('Error saving ticket.');
            }
        });
    });

    // Delete tickets
    function deleteTickets(ids) {
        $.ajax({
            url: window.location.origin+'/SupportTicket/ajax_delete',
            type: 'POST',
            data: { id: ids },
            traditional: true,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $("#ticketGrid").trigger('reloadGrid');
                } else {
                    alert('Failed to delete ticket(s).');
                }
            },
            error: function() {
                alert('Error deleting ticket(s).');
            }
        });
    }

});