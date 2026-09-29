<?php
require_once __DIR__ . '/../includes/database.php';
session_start();

$conn = box_database();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$uploadDir  = 'images/events/'; // folder simpan gambar
$successMsg = '';
$errorMsg   = '';

// DELETE event
if (isset($_GET['delete_id'])) {
    $deleteId = (int) $_GET['delete_id'];

    // optional: padam sekali gambar
    $res = $conn->query("SELECT image_path FROM events WHERE id = $deleteId");
    if ($res && $row = $res->fetch_assoc()) {
        if (!empty($row['image_path']) && file_exists($row['image_path'])) {
            @unlink($row['image_path']);
        }
    }

    $conn->query("DELETE FROM events WHERE id = $deleteId");
    $successMsg = "Event has been deleted.";
}

// INSERT event baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']);
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'] ?: null;
    $end_time   = $_POST['end_time'] ?: null;
    $location   = trim($_POST['location']);
    $price      = ($_POST['price'] !== '') ? (float) $_POST['price'] : 0.00;
    $short_desc = trim($_POST['short_desc']);
    $description= trim($_POST['description']);

    // basic validation
    if ($title === '' || $event_date === '' || $location === '') {
        $errorMsg = "Please fill in Title, Event Date and Location.";
    } else {

        // handle upload gambar (optional)
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $ext       = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName  = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($_FILES['image']['name'], PATHINFO_FILENAME)) . '.' . $ext;
            $target    = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imagePath = $target;
            } else {
                $errorMsg = "Failed to upload image.";
            }
        }

        if ($errorMsg === '') {
            $stmt = $conn->prepare("
                INSERT INTO events 
                (title, event_date, start_time, end_time, location, price, image_path, short_desc, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssssdsss",
                $title,
                $event_date,
                $start_time,
                $end_time,
                $location,
                $price,
                $imagePath,
                $short_desc,
                $description
            );

            if ($stmt->execute()) {
                $successMsg = "Event has been created.";
            } else {
                $errorMsg = "Something went wrong while saving the event.";
            }

            $stmt->close();
        }
    }
}

// ambil semua event untuk senarai
$events = $conn->query("SELECT * FROM events ORDER BY event_date DESC, start_time DESC");
?>

<?php include("headerAdmin.php"); ?>

    <title>Manage Events - Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
       

        .admin-wrapper {
            max-width: 1100px;
            margin: 3rem auto;
            padding: 0 1.5rem 3rem;
        }

        .admin-title {
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .alert {
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .alert.success {
            background: #e6f9ed;
            color: #147a3d;
            border: 1px solid #b4e5c6;
        }
        .alert.error {
            background: #ffecec;
            color: #c33434;
            border: 1px solid #f5b1b1;
        }

        .event-form-admin {
            background: #fff;
            padding: 1.5rem 1.8rem;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
            margin-bottom: 2rem;
        }

        .event-form-admin h2 {
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .form-group {
            flex: 1;
            min-width: 220px;
            display: flex;
            flex-direction: column;
            margin-bottom: 0.8rem;
        }
        .form-group label {
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        .form-group input,
        .form-group textarea {
            padding: 0.55rem 0.7rem;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 0.9rem;
            font-family: inherit;
        }
        .form-group textarea {
            resize: vertical;
        }

        .btn-admin {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 1.3rem;
            background: #111;
            color: #fff;
            border-radius: 8px;
            border: 1px solid #111;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .btn-admin:hover {
            opacity: 0.9;
        }

        .event-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }
        .event-table th,
        .event-table td {
            font-size: 0.9rem;
            padding: 0.7rem 0.8rem;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        .event-table th {
            background: #fafafa;
            text-align: left;
        }
        .event-table tr:last-child td {
            border-bottom: none;
        }
        .badge-free {
            color: #0a8a3a;
            font-weight: 600;
        }
        .admin-action a {
            color: #c33434;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .admin-action a:hover {
            text-decoration: underline;
        }
        .thumb-img {
            width: 70px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }
    </style>
<body>

<div class="admin-wrapper">
    <h1 class="admin-title">Manage Events</h1>

    <?php if ($successMsg): ?>
        <div class="alert success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <!-- FORM ADD EVENT -->
    <div class="event-form-admin">
        <h2>Add New Event</h2>
        <form method="post" action="admin_events.php" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" name="title" id="title" required>
                </div>
                <div class="form-group">
                    <label for="event_date">Event Date *</label>
                    <input type="date" name="event_date" id="event_date" required>
                </div>
                <div class="form-group">
                    <label for="start_time">Start Time</label>
                    <input type="time" name="start_time" id="start_time">
                </div>
                <div class="form-group">
                    <label for="end_time">End Time</label>
                    <input type="time" name="end_time" id="end_time">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="location">Location / Branch *</label>
                    <input type="text" name="location" id="location" placeholder="BOX KL, Lake Cabin, external venue..." required>
                </div>
                <div class="form-group">
                    <label for="price">Price (min) – RM</label>
                    <input type="number" step="0.01" name="price" id="price" placeholder="0.00 for free">
                </div>
                <div class="form-group">
                    <label for="image">Event Image</label>
                    <input type="file" name="image" id="image" accept="image/*">
                </div>
            </div>

            <div class="form-group">
                <label for="short_desc">Short Description</label>
                <input type="text" name="short_desc" id="short_desc" placeholder="Shown in card on browseEvent page">
            </div>

            <div class="form-group">
                <label for="description">Full Description (optional)</label>
                <textarea name="description" id="description" rows="3"></textarea>
            </div>

            <button type="submit" class="btn-admin">Save Event</button>
        </form>
    </div>

    <!-- TABLE LIST EVENT -->
    <h2 style="font-size:1.1rem; margin-bottom:0.5rem;">Existing Events</h2>
    <?php if ($events && $events->num_rows > 0): ?>
        <table class="event-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Event</th>
                    <th>Date & Time</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Image</th>
                    <th>Short Desc</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($ev = $events->fetch_assoc()): ?>
                    <tr>
                        <td><?= $ev['id'] ?></td>
                        <td><?= htmlspecialchars($ev['title']) ?></td>
                        <td>
                            <?= htmlspecialchars($ev['event_date']) ?><br>
                            <?php if (!empty($ev['start_time'])): ?>
                                <?= substr($ev['start_time'],0,5) ?>
                                <?php if (!empty($ev['end_time'])): ?>
                                    – <?= substr($ev['end_time'],0,5) ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($ev['location']) ?></td>
                        <td>
                            <?php if ($ev['price'] > 0): ?>
                                RM<?= number_format($ev['price'],2) ?>
                            <?php else: ?>
                                <span class="badge-free">Free</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($ev['image_path']) && file_exists($ev['image_path'])): ?>
                                <img src="<?= $ev['image_path'] ?>" class="thumb-img" alt="">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($ev['short_desc']) ?></td>
                        <td class="admin-action">
                            <a href="admin_events.php?delete_id=<?= $ev['id'] ?>" onclick="return confirm('Delete this event?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No events found yet.</p>
    <?php endif; ?>
</div>
<?php include("footerAdmin.php"); ?>

</body>
</html>
