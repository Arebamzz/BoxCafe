<?php
require_once __DIR__ . '/includes/database.php';
session_start();

$conn = box_database();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

include("header.php");
?>

<style>
    .event-section {
  padding: 2rem 2rem 3rem;
  background: #f5f5f5;
}

.event-grid {
  max-width: 1100px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.8rem;
}

.event-card {
  background: #ffffff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 10px 26px rgba(0,0,0,0.08);
  display: flex;
  flex-direction: column;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.event-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 16px 35px rgba(0,0,0,0.12);
}

.event-card img {
  width: 100%;
  height: 180px;
  object-fit: cover;
}

.event-content {
  padding: 1.1rem 1.3rem 1.3rem;
}

.event-content h3 {
  margin: 0 0 0.4rem;
  font-size: 1.2rem;
}

.event-date {
  margin: 0;
  font-size: 0.9rem;
  color: #555;
}

.event-location {
  margin: 0.4rem 0;
  font-size: 0.9rem;
  color: #777;
}

.event-short {
  font-size: 0.95rem;
  color: #555;
  margin-bottom: 0.6rem;
}

.event-price {
  font-weight: 600;
  margin-bottom: 0.6rem;
  color: #111;
}

.event-price.free {
  color: #0a8a3a;
}

.no-event-msg {
  text-align: center;
  color: #666;
  margin-top: 2rem;
}
/* ===== PREMIUM HERO TITLE (STYLE A) ===== */
.hero-title-premium {
    display: inline-block;
    background: #000;             /* black badge */
    color: #fff;                  /* white text */
    padding: 14px 32px;
    border-radius: 14px;
    font-size: 2.4rem;
    font-weight: 900;
    letter-spacing: 1.2px;
    position: relative;
    box-shadow: 0 10px 28px rgba(0,0,0,0.35);  /* soft glow shadow */
    animation: heroPopIn 0.7s ease-out forwards;
}

/* animated underline */
.hero-title-premium::after {
    content: "";
    position: absolute;
    left: 18px;
    right: 18px;
    bottom: -8px;
    height: 3px;
    background: #fff;
    opacity: 0.9;
    transform: scaleX(0);
    transform-origin: left;
    border-radius: 999px;
    animation: underlineGrow 0.7s ease-out forwards;
    animation-delay: 0.4s;
}

/* subtitle */
.hero-sub-premium {
    margin-top: 16px;
    font-size: 1.05rem;
    opacity: 0.9;
    animation: heroSubFade 0.8s ease-out forwards;
    animation-delay: 0.3s;
}

/* keyframes */
@keyframes heroPopIn {
    0%   { transform: translateY(18px) scale(0.9); opacity: 0; }
    60%  { transform: translateY(0) scale(1.05); opacity: 1; }
    100% { transform: translateY(0) scale(1); }
}

@keyframes underlineGrow {
    0%   { transform: scaleX(0); opacity: 0; }
    100% { transform: scaleX(1); opacity: 1; }
}

@keyframes heroSubFade {
    0%   { opacity: 0; transform: translateY(10px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* FULL BLACK HERO SECTION FOR EVENTS PAGE */
.event-hero {
    background: #000;
    color: #fff;
    padding: 4rem 2rem;
    text-align: center;
}

/* Make subtitle white */
.event-hero .hero-sub-premium {
    color: #ddd;
    opacity: 1;
}

/* Adjust the black badge so it blends nicely */
.event-hero .hero-title-premium {
    background: #111; /* slightly lighter black */
    box-shadow: 0 10px 30px rgba(0,0,0,0.6);
}



</style>

<section class="hero event-hero">
  <h1 class="hero-title-premium">Upcoming Events at BOX Cafe</h1>
  <p class="hero-sub-premium">
    Discover workshops, specials, and experiences happening soon across our BOX Cafe locations.
  </p>
</section>


<section class="event-section">
  <?php
    // ambil event yang tarikh >= hari ini, susun paling dekat dulu
    $today = date('Y-m-d');
    $sql = "
      SELECT * FROM events
      WHERE event_date >= '$today'
      ORDER BY event_date ASC, start_time ASC
    ";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0):
  ?>
    <div class="event-grid">
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="event-card">
          <?php if (!empty($row['image_path'])): ?>
            <img src="<?= htmlspecialchars($row['image_path']) ?>"
                 alt="<?= htmlspecialchars($row['title']) ?>">
          <?php endif; ?>

          <div class="event-content">
            <h3><?= htmlspecialchars($row['title']) ?></h3>

            <p class="event-date">
              <?php
                $dateObj = new DateTime($row['event_date']);
                echo $dateObj->format('d M Y');
              ?>
              <?php if (!empty($row['start_time'])): ?>
                • <?= substr($row['start_time'], 0, 5) ?>
                <?php if (!empty($row['end_time'])): ?>
                  – <?= substr($row['end_time'], 0, 5) ?>
                <?php endif; ?>
              <?php endif; ?>
            </p>

            <p class="event-location">
              📍 <?= htmlspecialchars($row['location']) ?>
            </p>

            <?php if (!empty($row['short_desc'])): ?>
              <p class="event-short">
                <?= htmlspecialchars($row['short_desc']) ?>
              </p>
            <?php endif; ?>

            <?php if ($row['price'] > 0): ?>
              <p class="event-price">From RM<?= number_format($row['price'], 2) ?></p>
            <?php else: ?>
              <p class="event-price free">Free entry</p>
            <?php endif; ?>

            <!-- future: detail page -->
            <!-- <a href="event_detail.php?id=<?= $row['id'] ?>" class="btn event-btn">View Details</a> -->
          </div>
        </div>
      <?php endwhile; ?>
    </div>

  <?php else: ?>
    <p class="no-event-msg">
      No upcoming events at the moment. Check back again soon.
    </p>
  <?php endif; ?>
</section>

<?php include("footer.php"); ?>
