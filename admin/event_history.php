<?php
session_start();
require_once 'db_connect.php';
require_once 'headerAdmin.php';

$stmt = $pdo->query("SELECT * FROM event_bookings ORDER BY created_at DESC");
$events = $stmt->fetchAll();
?>

<div class="content">
  <h2>Event Booking History</h2>
  <table border="1" cellpadding="10" cellspacing="0">
    <thead>
      <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Type</th>
        <th>Date</th>
        <th>Location</th>
        <th>Budget (RM)</th>
        <th>Pax</th>
        <th>Submitted At</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($events as $e): ?>
        <tr>
          <td><?= $e['id'] ?></td>
          <td><?= htmlspecialchars($e['full_name']) ?></td>
          <td><?= htmlspecialchars($e['phone']) ?></td>
          <td><?= htmlspecialchars($e['email']) ?></td>
          <td><?= htmlspecialchars($e['event_type']) ?></td>
          <td><?= $e['event_date'] ?></td>
          <td><?= htmlspecialchars($e['location']) ?></td>
          <td><?= number_format($e['budget'], 2) ?></td>
          <td><?= $e['pax'] ?></td>
          <td><?= $e['created_at'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once 'footerAdmin.php'; ?>
