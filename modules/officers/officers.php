<?php
include_once __DIR__ . '/../include/auth_required.php';

include __DIR__ . '/../../database/connect.php';

// Check if user is admin
$is_admin = isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1;

// Get all partylists (exclude archived)
$partylists = mysqli_query($conn, "SELECT * FROM partylist WHERE is_archived = 0 ORDER BY name ASC");

// Handle partylist selection for bulletin display
if (isset($_POST['set_bulletin_partylist'])) {
    // First, set all partylists to not selected
    mysqli_query($conn, "UPDATE partylist SET is_selected = 0");
    
    // Then, set the selected partylist
    if (!empty($_POST['set_bulletin_partylist'])) {
        $partylist_id = intval($_POST['set_bulletin_partylist']);
        mysqli_query($conn, "UPDATE partylist SET is_selected = 1 WHERE id = $partylist_id");
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get the currently selected partylist (exclude archived)
$selectedPartylist = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM partylist WHERE is_selected = 1 AND is_archived = 0 LIMIT 1"));
$selectedPartylistId = $selectedPartylist ? $selectedPartylist['id'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Partylist Overview</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    body {
      background: #ffffff;
      color: #333;
      min-height: 100vh;
      line-height: 1.6;
    }

    .header {
      background: #7B0000;
      color: white;
      padding: 15px 20px;
      font-size: 2em;
      font-weight: 600;
      letter-spacing: -0.5px;
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
      border-bottom: 2px solid rgba(255, 255, 255, 0.1);
      margin: 20px auto;
      display: flex;
      justify-content: center;
      align-items: center;
      width: 90%;
      max-width: 1200px;
      border-radius: 10px;
      position: relative;
      z-index: 1;
    }

    .container {
      width: 90%;
      max-width: 1200px;
      background: #ffffff;
      margin: 30px auto;
      padding: 70px 30px 30px;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid #e0e0e0;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
    }

    .partylist-card {
      background: #ffffff;
      border: 1px solid #e0e0e0;
      padding: 20px;
      margin: 15px 0;
      border-radius: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .partylist-card:hover {
      transform: translateY(-2px);
      background: #f8f9fa;
      border-color: #7B0000;
      box-shadow: 0 4px 12px rgba(123, 0, 0, 0.1);
    }

    .partylist-info {
      font-weight: 500;
      font-size: 1.1rem;
      color: #333;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .partylist-info i {
      color: #7B0000;
    }

    .button-group {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn {
      padding: 10px 20px;
      border-radius: 30px;
      text-decoration: none;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.3s ease;
      font-size: 0.95rem;
      border: 1px solid #e0e0e0;
      min-height: 44px; /* Touch target size */
      min-width: 44px;
    }

    .btn.manage {
      background: rgba(123, 0, 0, 0.1);
      color: #7B0000;
      border: 1px solid #7B0000;
    }

    .btn.manage:hover {
      background: rgba(123, 0, 0, 0.2);
      transform: translateY(-2px);
    }

    .btn.delete {
      background: rgba(123, 0, 0, 0.1);
      color: #7B0000;
      width: 44px;
      height: 44px;
      min-width: 44px;
      min-height: 44px;
      padding: 0;
      font-size: 1.2rem;
      border-radius: 50%;
      border: 1px solid #7B0000;
    }

    .btn.delete:hover {
      background: rgba(123, 0, 0, 0.2);
      color: #7B0000;
      transform: translateY(-2px);
    }

    .add-button {
      text-align: right;
      margin-bottom: 30px;
      display: flex;
      gap: 15px;
      justify-content: flex-end;
    }

    .add-button a {
      background: rgba(123, 0, 0, 0.1);
      color: #7B0000;
      padding: 12px 24px;
      border-radius: 30px;
      text-decoration: none;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      border: 1px solid #7B0000;
      justify-content: center;
      min-height: 44px; /* Touch target size */
    }

    .add-button a:hover {
      background: rgba(123, 0, 0, 0.2);
      transform: translateY(-2px);
    }
    
    .add-button a.archive-btn {
      background: rgba(128, 128, 128, 0.1);
      border-color: rgba(128, 128, 128, 0.2);
      color: #666;
    }
    
    .add-button a.archive-btn:hover {
      background: rgba(128, 128, 128, 0.2);
    }

    .bulletin-selector {
      background: #ffffff;
      padding: 20px;
      margin-bottom: 25px;
      border-radius: 12px;
      border: 1px solid #e0e0e0;
    }

    .bulletin-selector form {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    .bulletin-selector select {
      padding: 12px 16px;
      border-radius: 12px;
      flex-grow: 1;
      background: #ffffff;
      border: 1px solid #e0e0e0;
      color: #333;
      font-size: 1em;
      outline: none;
      transition: all 0.3s ease;
    }

    .bulletin-selector select:focus {
      border-color: #7B0000;
      background: #ffffff;
      box-shadow: 0 0 0 3px rgba(123, 0, 0, 0.1);
    }

    .bulletin-selector select option {
      background: #ffffff;
      color: #333;
    }

    .bulletin-selector button {
      background: rgba(123, 0, 0, 0.1);
      color: #7B0000;
      border: 1px solid #7B0000;
      padding: 12px 24px;
      border-radius: 30px;
      cursor: pointer;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      font-size: 0.95rem;
    }

    .bulletin-selector button:hover {
      background: rgba(123, 0, 0, 0.2);
      transform: translateY(-2px);
    }

    @media (max-width: 768px) {
      .header {
        padding: 12px 15px;
        font-size: 1.6em;
      }
      .container {
        width: 95%;
        padding: 15px;
        margin: 15px auto;
      }
      .add-button {
        position: static;
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-bottom: 20px;
      }
      .add-button a {
        padding: 10px 16px;
        font-size: 0.9em;
        flex: 1;
        min-width: 140px;
        max-width: 200px;
      }
      .bulletin-selector form {
        flex-direction: column;
      }
      .bulletin-selector select,
      .bulletin-selector button {
        width: 100%;
      }
      .partylist-card {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      .button-group {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
      }
      .btn {
        min-height: 44px; /* Touch target size */
        min-width: 44px;
        padding: 10px 18px;
        font-size: 0.9rem;
      }
      .btn.delete {
        width: 44px;
        height: 44px;
        min-width: 44px;
        min-height: 44px;
      }
    }
    
    @media (max-width: 480px) {
      .add-button {
        position: static;
        margin-bottom: 15px;
        gap: 8px;
      }
      .add-button a {
        padding: 8px 12px;
        font-size: 0.8em;
        min-width: 120px;
        max-width: 160px;
        justify-content: center;
      }
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 1000;
      overflow-y: auto;
      padding: 120px 0;
      backdrop-filter: blur(5px);
    }

    .modal.show {
      display: flex;
      align-items: flex-start;
      justify-content: center;
    }

    .modal-content {
      background: #ffffff;
      width: min(450px, 95%);
      margin: 0 auto;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid #e0e0e0;
      transform: scale(0.7);
      opacity: 0;
      transition: all 0.3s ease;
      position: relative;
    }

    .modal.show .modal-content {
      transform: scale(1);
      opacity: 1;
    }

    .modal-header {
      padding: 20px 20px 0;
      color: #7B0000;
      font-size: 1.4em;
      font-weight: 600;
    }

    .modal-header h3 {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0;
      font-size: 1em;
    }

    .modal-body {
      padding: 20px;
      color: #333;
      text-align: center;
    }

    .modal-footer {
      padding: 20px;
      display: flex;
      justify-content: center;
      gap: 12px;
      border-top: 1px solid #e0e0e0;
    }

    .modal-footer button {
      padding: 8px 16px;
      border-radius: 30px;
      cursor: pointer;
      font-size: 0.9em;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
    }

    .modal-footer .cancel-btn {
      background: rgba(123, 0, 0, 0.1);
      color: #7B0000;
      border: 1px solid #7B0000;
    }

    .modal-footer .cancel-btn:hover {
      background: rgba(123, 0, 0, 0.2);
      transform: translateY(-2px);
    }

    .modal-footer .confirm-delete-btn {
      background: rgba(123, 0, 0, 0.1);
      color: #7B0000;
      border: 1px solid #7B0000;
    }

    .modal-footer .confirm-delete-btn:hover {
      background: rgba(123, 0, 0, 0.2);
      transform: translateY(-2px);
    }

    .close {
      position: absolute;
      top: 15px;
      right: 20px;
      background: none;
      border: none;
      color: #7B0000;
      font-size: 24px;
      cursor: pointer;
      padding: 0;
      width: auto;
      margin: 0;
    }

    .close:hover {
      color: #7B0000;
      opacity: 0.8;
    }

    @media (max-width: 768px) {
      .modal-content {
        width: 95%;
        margin: 0 15px;
      }
    }
  </style>
</head>
<body>

<?php include '../include/header.php'; ?>

<div class="header">
  <span>Partylist Officers</span>
</div>

<div class="container">
  <div class="bulletin-selector">
    <form method="POST">
      <select name="set_bulletin_partylist">
        <option value="">All Officers</option>
        <?php 
        mysqli_data_seek($partylists, 0);
        while ($party = mysqli_fetch_assoc($partylists)): 
        ?>
          <option value="<?= $party['id'] ?>" <?= $selectedPartylistId == $party['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($party['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
      <button type="submit">
        <i class="fas fa-bullhorn"></i>
        Set for Bulletin Display
      </button>
    </form>
  </div>

  <?php if (isset($_SESSION['success'])): ?>
    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
      <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
      <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
  <?php endif; ?>

  <div class="add-button">
    <a href="add_partylist.php">
      <i class="fas fa-plus"></i>
      Add Partylist
    </a>
    <?php if ($is_admin): ?>
    <a href="archived_partylists.php" class="archive-btn">
      <i class="fas fa-archive"></i>
      View Archived
    </a>
    <?php endif; ?>
  </div>

  <?php 
  mysqli_data_seek($partylists, 0);
  while ($party = mysqli_fetch_assoc($partylists)): 
  ?>
    <div class="partylist-card">
      <div class="partylist-info">
        <i class="fas fa-users"></i>
        <?= htmlspecialchars($party['name']) ?>
      </div>
      <div class="button-group">
        <a href="update_officer.php?partylist_id=<?= $party['id'] ?>" class="btn manage">
          <i class="fas fa-user-edit"></i>
          Manage
        </a>
        <button class="btn delete" onclick="showArchiveModal(<?= $party['id'] ?>, '<?= htmlspecialchars(addslashes($party['name'])) ?>')">
          <i class="fas fa-archive"></i>
        </button>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<!-- Archive Confirmation Modal -->
<div class="modal" id="archiveModal">
  <div class="modal-content">
    <div class="modal-header">
      <h3><i class="fas fa-archive"></i> Archive Partylist</h3>
      <button type="button" class="close" onclick="closeArchiveModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p>Are you sure you want to archive this partylist?</p>
      <p style="font-weight: 600; margin-top: 10px;" id="partylistName"></p>
      <p style="color: rgba(255, 255, 255, 0.6); font-size: 0.9em; margin-top: 10px;">
        <i class="fas fa-info-circle"></i> This partylist and all associated officers will be moved to archives and can be restored later.
      </p>
    </div>
    <div class="modal-footer">
      <button type="button" class="cancel-btn" onclick="closeArchiveModal()">
        <i class="fas fa-times"></i> Cancel
      </button>
      <button type="button" class="confirm-delete-btn" onclick="confirmArchive()">
        <i class="fas fa-archive"></i> Archive
      </button>
    </div>
  </div>
</div>

<script>
let currentPartylistId = null;

function showArchiveModal(partylistId, partylistName) {
  currentPartylistId = partylistId;
  document.getElementById('partylistName').textContent = partylistName;
  document.getElementById('archiveModal').classList.add('show');
}

function closeArchiveModal() {
  document.getElementById('archiveModal').classList.remove('show');
  setTimeout(() => {
    currentPartylistId = null;
    document.getElementById('partylistName').textContent = '';
  }, 300);
}

function confirmArchive() {
  if (!currentPartylistId) return;
  
  const submitBtn = document.querySelector('.confirm-delete-btn');
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
  
  fetch('archive_partylist.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `partylist_id=${encodeURIComponent(currentPartylistId)}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      closeArchiveModal();
      showSuccess(data.message || 'Partylist archived successfully');
      setTimeout(() => location.reload(), 1000);
    } else {
      throw new Error(data.message || 'Failed to archive partylist');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showError(error.message || 'Failed to archive partylist. Please try again.');
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-archive"></i> Archive';
  });
}

function showSuccess(message) {
  const alert = document.createElement('div');
  alert.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #d4edda; color: #155724; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10000; display: flex; align-items: center; gap: 10px;';
  alert.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
  document.body.appendChild(alert);
  setTimeout(() => alert.remove(), 3000);
}

function showError(message) {
  const alert = document.createElement('div');
  alert.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10000; display: flex; align-items: center; gap: 10px;';
  alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
  document.body.appendChild(alert);
  setTimeout(() => alert.remove(), 5000);
}

// Close modal when clicking outside
window.onclick = function(event) {
  const modal = document.getElementById('archiveModal');
  if (event.target === modal) {
    closeArchiveModal();
  }
}

// Add keyboard support
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    closeArchiveModal();
  }
});
</script>

</body>
</html>
