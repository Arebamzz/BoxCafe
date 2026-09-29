<?php
require_once __DIR__ . '/includes/database.php';
session_start();
$conn = box_database();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

include("header.php");

// Helper: calculate promo price (if active + within date)
function promoPrice($row) {
    $price = floatval($row['price']);
    $active = isset($row['promo_active']) ? intval($row['promo_active']) : 0;

    if ($active !== 1) return null;

    $type = isset($row['discount_type']) ? $row['discount_type'] : null;
    $value = isset($row['discount_value']) ? floatval($row['discount_value']) : 0;

    if (!$type || $value <= 0) return null;

    $today = date('Y-m-d');
    $start = !empty($row['promo_start']) ? $row['promo_start'] : null;
    $end   = !empty($row['promo_end']) ? $row['promo_end'] : null;

    if ($start && $today < $start) return null;
    if ($end && $today > $end) return null;

    if ($type === 'percent') {
        $newPrice = $price - ($price * ($value / 100));
    } else {
        $newPrice = $price - $value;
    }

    if ($newPrice < 0) $newPrice = 0;
    return round($newPrice, 2);
}
?>
<link rel="stylesheet" href="styleMenu.css">

<style>
/* ===== BLACK HERO + PROMO AREA ===== */

.promo-wrapper {
    background: #000;              /* full black background */
    color: #fff;
    padding: 3rem 0 4rem;
    text-align: center;
}

/* title + subtitle dalam block hitam */
.promo-wrapper .menu-hero-title {
    font-size: 2.4rem;
    font-weight: 800;
    margin: 0;
}

.promo-wrapper .menu-hero-sub {
    margin-top: 0.8rem;
    color: #dddddd;
    font-size: 1.05rem;
}

/* ===== PROMO SLIDER CARD ===== */

.promo-slider {
    width: 100%;
    max-width: 460px;
    height: 460px;
    margin: 2rem auto 0;
    border-radius: 24px;
    overflow: hidden;
    position: relative;
    background: #000;
    border: 1px solid rgba(255,255,255,0.18);
    box-shadow: 0 20px 45px rgba(0,0,0,0.7);
}

/* slides */
.promo-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    transform: scale(1.03);
    transition: opacity 0.7s ease-out, transform 0.7s ease-out;
}

.promo-slide.active {
    opacity: 1;
    transform: scale(1);
    z-index: 2;
}

.promo-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* dots */
.promo-dots {
    position: absolute;
    bottom: 16px;
    left: 0;
    width: 100%;
    text-align: center;
}

.promo-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    margin: 0 4px;
    border-radius: 999px;
    background: rgba(255,255,255,0.4);
    cursor: pointer;
    transition: transform 0.2s, background 0.2s, width 0.2s;
}

.promo-dot.active {
    background: #ffffff;
    width: 22px;
    transform: translateY(-1px);
}

/* ===== END BLACK AREA ===== */

/* promo price style (NEW) */
.price .old-price{
    text-decoration: line-through;
    color:#888;
    margin-right: 6px;
}
.price .new-price{
    font-weight: 800;
}
.promo-tag{
    display:inline-block;
    margin-left: 6px;
    font-size: 12px;
    padding: 3px 8px;
    border-radius: 999px;
    background:#111;
    color:#fff;
}
</style>

<!-- 🔥 Black hero + promo block -->
<section class="promo-wrapper">
    <h1 class="menu-hero-title">Our Menu</h1>
    <p class="menu-hero-sub">Explore our carefully crafted food and drink offerings.</p>

    <div class="promo-slider">
        <div class="promo-slides">
            <div class="promo-slide active">
                <img src="images/promo/Studentpromo.jpg" alt="Student Promo">
            </div>
            <div class="promo-slide">
                <img src="images/promo/halfprice.jpg" alt="50% Off">
            </div>
            <div class="promo-slide">
                <img src="images/promo/Lunchset.jpg" alt="Lunch Set">
            </div>
            <div class="promo-slide">
                <img src="images/promo/Bestseller.jpg" alt="Bestseller">
            </div>
            <div class="promo-slide">
                <img src="images/promo/weekend promo.jpg" alt="Weekend Promo">
            </div>
        </div>

        <div class="promo-dots">
            <span class="promo-dot active" data-index="0"></span>
            <span class="promo-dot" data-index="1"></span>
            <span class="promo-dot" data-index="2"></span>
            <span class="promo-dot" data-index="3"></span>
            <span class="promo-dot" data-index="4"></span>
        </div>
    </div>
</section>

<section class="menu-section">
  <h2>Food</h2>

  <!-- 🥘 Main Course & Rice -->
  <h3 class="menu-subheading">Main Course & Rice</h3>
  <div class="menu-slider">
    <button class="slider-arrow slider-arrow-left" type="button" aria-label="Scroll left">&#10094;</button>

  <div class="menu-gallery">
    <?php
    $result = $conn->query("SELECT * FROM menu_items WHERE subcategory_id = 1");
    while ($row = $result->fetch_assoc()):
      $p = promoPrice($row);
    ?>
      <div class="menu-item">
        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        <h4><?= htmlspecialchars($row['name']) ?></h4>

        <p class="price">
          <?php if ($p !== null): ?>
            <span class="old-price">RM<?= number_format($row['price'], 2) ?></span>
            <span class="new-price">RM<?= number_format($p, 2) ?></span>
            <span class="promo-tag">PROMO</span>
          <?php else: ?>
            RM<?= number_format($row['price'], 2) ?>
          <?php endif; ?>
        </p>

        <p><?= htmlspecialchars($row['description']) ?></p>
        <form method="post" action="add_to_cart.php">
          <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
          <input type="hidden" name="type" value="menu">
          <input type="hidden" name="quantity" value="1">
          <input type="hidden" name="remark" value="">
          <button class="btn" type="submit">Add to Bag</button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
    <button class="slider-arrow slider-arrow-right" type="button" aria-label="Scroll right">&#10095;</button>
  </div>

  <!-- 🍝 Pasta -->
  <h3 class="menu-subheading">Pasta</h3>
  <div class="menu-slider">
    <button class="slider-arrow slider-arrow-left" type="button" aria-label="Scroll left">&#10094;</button>

  <div class="menu-gallery">
    <?php
    $result = $conn->query("SELECT * FROM menu_items WHERE subcategory_id = 2");
    while ($row = $result->fetch_assoc()):
      $p = promoPrice($row);
    ?>
      <div class="menu-item">
        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        <h4><?= htmlspecialchars($row['name']) ?></h4>

        <p class="price">
          <?php if ($p !== null): ?>
            <span class="old-price">RM<?= number_format($row['price'], 2) ?></span>
            <span class="new-price">RM<?= number_format($p, 2) ?></span>
            <span class="promo-tag">PROMO</span>
          <?php else: ?>
            RM<?= number_format($row['price'], 2) ?>
          <?php endif; ?>
        </p>

        <p><?= htmlspecialchars($row['description']) ?></p>
        <form method="post" action="add_to_cart.php">
          <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
          <input type="hidden" name="type" value="menu">
          <input type="hidden" name="quantity" value="1">
          <input type="hidden" name="remark" value="">
          <button class="btn" type="submit">Add to Bag</button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
    <button class="slider-arrow slider-arrow-right" type="button" aria-label="Scroll right">&#10095;</button>
  </div>

  <!-- 🍳 Breakfast -->
  <h3 class="menu-subheading">Breakfast</h3>
  <div class="menu-slider">
    <button class="slider-arrow slider-arrow-left" type="button" aria-label="Scroll left">&#10094;</button>

  <div class="menu-gallery">
    <?php
    $result = $conn->query("SELECT * FROM menu_items WHERE subcategory_id = 3");
    while ($row = $result->fetch_assoc()):
      $p = promoPrice($row);
    ?>
      <div class="menu-item">
        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        <h4><?= htmlspecialchars($row['name']) ?></h4>

        <p class="price">
          <?php if ($p !== null): ?>
            <span class="old-price">RM<?= number_format($row['price'], 2) ?></span>
            <span class="new-price">RM<?= number_format($p, 2) ?></span>
            <span class="promo-tag">PROMO</span>
          <?php else: ?>
            RM<?= number_format($row['price'], 2) ?>
          <?php endif; ?>
        </p>

        <p><?= htmlspecialchars($row['description']) ?></p>
        <form method="post" action="add_to_cart.php">
          <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
          <input type="hidden" name="type" value="menu">
          <input type="hidden" name="quantity" value="1">
          <input type="hidden" name="remark" value="">
          <button class="btn" type="submit">Add to Bag</button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
    <button class="slider-arrow slider-arrow-right" type="button" aria-label="Scroll right">&#10095;</button>
  </div>

  <!-- 🥤 Drinks -->
  <h3 class="menu-subheading">Drinks</h3>
  <div class="menu-slider">
    <button class="slider-arrow slider-arrow-left" type="button" aria-label="Scroll left">&#10094;</button>

  <div class="menu-gallery">
    <?php
    $result = $conn->query("SELECT * FROM menu_items WHERE subcategory_id = 6");
    while ($row = $result->fetch_assoc()):
      $p = promoPrice($row);
    ?>
      <div class="menu-item">
        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        <h4><?= htmlspecialchars($row['name']) ?></h4>

        <p class="price">
          <?php if ($p !== null): ?>
            <span class="old-price">RM<?= number_format($row['price'], 2) ?></span>
            <span class="new-price">RM<?= number_format($p, 2) ?></span>
            <span class="promo-tag">PROMO</span>
          <?php else: ?>
            RM<?= number_format($row['price'], 2) ?>
          <?php endif; ?>
        </p>

        <p><?= htmlspecialchars($row['description']) ?></p>
        <form method="post" action="add_to_cart.php">
          <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
          <input type="hidden" name="type" value="menu">
          <input type="hidden" name="quantity" value="1">
          <input type="hidden" name="remark" value="">
          <button class="btn" type="submit">Add to Bag</button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
    <button class="slider-arrow slider-arrow-right" type="button" aria-label="Scroll right">&#10095;</button>
  </div>

  <!-- 🍰 Dessert -->
  <h3 class="menu-subheading">Dessert</h3>
  <div class="menu-slider">
    <button class="slider-arrow slider-arrow-left" type="button" aria-label="Scroll left">&#10094;</button>

  <div class="menu-gallery">
    <?php
    $result = $conn->query("SELECT * FROM menu_items WHERE subcategory_id = 5");
    while ($row = $result->fetch_assoc()):
      $p = promoPrice($row);
    ?>
      <div class="menu-item">
        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        <h4><?= htmlspecialchars($row['name']) ?></h4>

        <p class="price">
          <?php if ($p !== null): ?>
            <span class="old-price">RM<?= number_format($row['price'], 2) ?></span>
            <span class="new-price">RM<?= number_format($p, 2) ?></span>
            <span class="promo-tag">PROMO</span>
          <?php else: ?>
            RM<?= number_format($row['price'], 2) ?>
          <?php endif; ?>
        </p>

        <p><?= htmlspecialchars($row['description']) ?></p>
        <form method="post" action="add_to_cart.php">
          <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
          <input type="hidden" name="type" value="menu">
          <input type="hidden" name="quantity" value="1">
          <input type="hidden" name="remark" value="">
          <button class="btn" type="submit">Add to Bag</button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
    <button class="slider-arrow slider-arrow-right" type="button" aria-label="Scroll right">&#10095;</button>
  </div>

  <!-- 🍟 Snacks -->
  <h3 class="menu-subheading">Snacks</h3>
  <div class="menu-slider">
    <button class="slider-arrow slider-arrow-left" type="button" aria-label="Scroll left">&#10094;</button>

  <div class="menu-gallery">
    <?php
    $result = $conn->query("SELECT * FROM menu_items WHERE subcategory_id = 4");
    while ($row = $result->fetch_assoc()):
      $p = promoPrice($row);
    ?>
      <div class="menu-item">
        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        <h4><?= htmlspecialchars($row['name']) ?></h4>

        <p class="price">
          <?php if ($p !== null): ?>
            <span class="old-price">RM<?= number_format($row['price'], 2) ?></span>
            <span class="new-price">RM<?= number_format($p, 2) ?></span>
            <span class="promo-tag">PROMO</span>
          <?php else: ?>
            RM<?= number_format($row['price'], 2) ?>
          <?php endif; ?>
        </p>

        <p><?= htmlspecialchars($row['description']) ?></p>
        <form method="post" action="add_to_cart.php">
          <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
          <input type="hidden" name="type" value="menu">
          <input type="hidden" name="quantity" value="1">
          <input type="hidden" name="remark" value="">
          <button class="btn" type="submit">Add to Bag</button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
    <button class="slider-arrow slider-arrow-right" type="button" aria-label="Scroll right">&#10095;</button>
  </div>

  <!-- 🍱 Special -->
  <h3 class="menu-subheading">Special</h3>
  <div class="menu-slider">
    <button class="slider-arrow slider-arrow-left" type="button" aria-label="Scroll left">&#10094;</button>

  <div class="menu-gallery">
    <?php
    $result = $conn->query("SELECT * FROM menu_items WHERE subcategory_id = 7");
    while ($row = $result->fetch_assoc()):
      $p = promoPrice($row);
    ?>
      <div class="menu-item">
        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        <h4><?= htmlspecialchars($row['name']) ?></h4>

        <p class="price">
          <?php if ($p !== null): ?>
            <span class="old-price">RM<?= number_format($row['price'], 2) ?></span>
            <span class="new-price">RM<?= number_format($p, 2) ?></span>
            <span class="promo-tag">PROMO</span>
          <?php else: ?>
            RM<?= number_format($row['price'], 2) ?>
          <?php endif; ?>
        </p>

        <p><?= htmlspecialchars($row['description']) ?></p>
        <form method="post" action="add_to_cart.php">
          <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
          <input type="hidden" name="type" value="menu">
          <input type="hidden" name="quantity" value="1">
          <input type="hidden" name="remark" value="">
          <button class="btn" type="submit">Add to Bag</button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
    <button class="slider-arrow slider-arrow-right" type="button" aria-label="Scroll right">&#10095;</button>
  </div>

</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.promo-slide');
    const dots = document.querySelectorAll('.promo-dot');
    let current = 0;

    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
        current = index;
    }

    function nextSlide() {
        const next = (current + 1) % slides.length;
        showSlide(next);
    }

    // Auto slide every 4s
    let interval = setInterval(nextSlide, 4000);

    // Click on dots
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            const index = parseInt(dot.getAttribute('data-index'));
            showSlide(index);
            clearInterval(interval);
            interval = setInterval(nextSlide, 4000);
        });
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".menu-slider").forEach(slider => {
    const gallery = slider.querySelector(".menu-gallery");
    const leftBtn = slider.querySelector(".slider-arrow-left");
    const rightBtn = slider.querySelector(".slider-arrow-right");

    if (!gallery || !leftBtn || !rightBtn) return;

    const scrollAmount = () => Math.floor(gallery.clientWidth * 0.85);

    function updateButtons() {
      const maxScrollLeft = gallery.scrollWidth - gallery.clientWidth;
      leftBtn.disabled = gallery.scrollLeft <= 5;
      rightBtn.disabled = gallery.scrollLeft >= (maxScrollLeft - 5);
    }

    leftBtn.addEventListener("click", () => {
      gallery.scrollBy({ left: -scrollAmount(), behavior: "smooth" });
    });

    rightBtn.addEventListener("click", () => {
      gallery.scrollBy({ left: scrollAmount(), behavior: "smooth" });
    });

    gallery.addEventListener("scroll", updateButtons);
    window.addEventListener("resize", updateButtons);

    updateButtons();
  });
});
</script>

<?php include("footer.php"); ?>
