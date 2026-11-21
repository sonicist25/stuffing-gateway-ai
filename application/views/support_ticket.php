<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Support Ticketing System</title>

<!-- jqGrid CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/free-jqgrid@4.15.5/dist/css/ui.jqgrid.min.css" />
<!-- jQuery UI theme -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css" />
<!-- Bootstrap CSS for modals -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<h1>Support Ticketing System</h1>

<div id="grid_container">
    <table id="ticketGrid"></table>
    <div id="ticketPager"></div>
</div>

<!-- Modal for Add/Edit Ticket -->
<div class="modal fade" id="ticketModal" tabindex="-1" role="dialog" aria-labelledby="ticketModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="ticketForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="ticketModalLabel">Add Ticket</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
      <div class="modal-body">
            <input type="hidden" id="ticket_id" name="id" />
            
      <?php if ($this->session->userdata('user_id') !== 0 && $this->session->userdata('user_id') !== 609): ?>
            <div class="form-group">
                <label for="ticket_description">Trouble Description (ID and Password for remote)</label>
                <textarea class="form-control" id="ticket_description" name="description" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="ticket_status">Status</label>
                <select class="form-control" id="ticket_status" name="status" required>
                    <option value="Open">Open</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
        <?php else: ?>
          <div class="form-group">
                <label for="ticket_description">Trouble Description (ID and Password for remote)</label>
                <textarea class="form-control" id="ticket_description" name="description" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="ticket_action">Action</label>
                <textarea class="form-control" id="ticket_action" name="action" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="ticket_action_date">Action Date</label>
                <input type="datetime-local" class="form-control" id="ticket_action_date" name="action_date" required />
            </div>
            <div class="form-group">
                <label for="ticket_finish_date">Finish Date</label>
                <input type="datetime-local" class="form-control" id="ticket_finish_date" name="finish_date"/>
            </div>
            <div class="form-group">
                <label for="ticket_status">Status</label>
                <select class="form-control" id="ticket_status" name="status" required>
                    <option value="In Progress">In Progress</option>
                </select>
            </div>
        <?php endif; ?>

        </div>

        
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" id="saveTicketBtn">Save</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        </div>
        
      </div>
    </form>
  </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- jQuery UI -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<!-- jqGrid -->
<script src="https://cdn.jsdelivr.net/npm/free-jqgrid@4.15.5/dist/jquery.jqgrid.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
