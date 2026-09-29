<?php include("header.php"); ?>
<link rel="stylesheet" href="style.css">

<section class="hero home-hero-black">
  <h1 class="home-hero-title">Welcome to BOX Cafe</h1>
  <p class="home-hero-sub">
    Where creativity meets comfort. Box Cafe offers a space to unwind, work, and savor artisanal coffee in a minimalist, modern atmosphere.
  </p>
  <p class="home-hero-sub home-hero-sub-secondary">
    Order your favorite menu or book a cozy event.
  </p>

  <div class="hero-buttons">
    <a href="menu.php" class="btn">Order Now</a>
    <a href="event.php" class="btn">Book an Event</a>
    <a href="browseEvent.php" class="btn">Browse Upcoming Events</a>
  </div>
</section>

<!-- ===== BRANCHES SECTION ===== -->
<section class="branches-section">
  <div class="branches-header">
    <h2>Our Locations</h2>
    <p>Visit any of our BOX branches around Klang Valley for coffee, conversations and cozy corners.</p>
  </div>

  <div class="location-grid">
    <div class="location-item" onclick="openModal('kl')">
      <div class="location-img-wrap">
        <img src="images/Boxkl.png" alt="Box KL" />
      </div>
      <h3>Box KL</h3>
      <p class="location-meta">Kuala Lumpur City Centre</p>
    </div>

    <div class="location-item" onclick="openModal('lake')">
      <div class="location-img-wrap">
        <img src="images/LakeCabin.png" alt="Lake Cabin" />
      </div>
      <h3>Lake Cabin</h3>
      <p class="location-meta">Anjung Floria, Putrajaya</p>
    </div>

    <div class="location-item" onclick="openModal('fifteen')">
      <div class="location-img-wrap">
        <img src="images/Box15.png" alt="Box Fifteen" />
      </div>
      <h3>Box Fifteen</h3>
      <p class="location-meta">SS15, Subang Jaya</p>
    </div>

    <div class="location-item" onclick="openModal('fzhd')">
      <div class="location-img-wrap">
        <img src="images/Fzhd.png" alt="FZHD" />
      </div>
      <h3>Box FZHD</h3>
      <p class="location-meta">Bukit Jelutong, Shah Alam</p>
    </div>
  </div>
</section>

<!-- (Optional) Highlights section – you can add content later -->
<section class="highlights">
</section>

<!-- ================== MODALS ================== -->

<!-- 📍 Box KL -->
<div id="modal-kl" class="modal">
  <div class="modal-content">
    <button class="close" onclick="closeModal('kl')">&times;</button>
    <h2>Box KL</h2>
    <p class="modal-tag">Has outdoor seating · Specialty coffee</p>
    <p><strong>Address:</strong> 34D & 34E, Jalan Hang Lekiu, Kuala Lumpur City Centre, 50050 Kuala Lumpur, Federal Territory of Kuala Lumpur</p>
    <p><strong>Phone:</strong> <a href="tel:0176244791">017-624 4791</a></p>
    <p><strong>Hours:</strong> Open · Closes 12 am</p>
    <p><strong>Price per person:</strong> RM 20–40</p>
    <iframe 
      src="https://www.google.com/maps/embed/v1/place?key=AIzaSyB9mo6ojcedxXITE4YEKupXj0ZifaaGP7I&q=34D+Jalan+Hang+Lekiu+Kuala+Lumpur"
      width="100%" height="280" style="border:0; border-radius: 10px;" allowfullscreen="" loading="lazy">
    </iframe>
    <a class="gmaps-link" target="_blank"
       href="https://www.google.com/maps/search/?api=1&query=34D+Jalan+Hang+Lekiu+Kuala+Lumpur">
       Open in Google Maps
    </a>
  </div>
</div>

<!-- 📍 Lake Cabin -->
<div id="modal-lake" class="modal">
  <div class="modal-content">
    <button class="close" onclick="closeModal('lake')">&times;</button>
    <h2>Lake Cabin at Anjung Floria</h2>
    <p class="modal-tag">Lakeside vibes · Perfect for sunset coffee</p>
    <p><strong>Address:</strong> Anjung Floria, Jalan Tuanku Abdul Rahman, Presint 4, 62000 Putrajaya</p>
    <p><strong>Phone:</strong> <a href="tel:0105432234">010-543 2234</a></p>
    <p><strong>Hours:</strong> Opens 8 am Thu</p>
    <p><strong>Price per person:</strong> RM 20–40</p>
    <iframe 
      src="https://www.google.com/maps/embed/v1/place?key=AIzaSyB9mo6ojcedxXITE4YEKupXj0ZifaaGP7I&q=Anjung+Floria+Putrajaya"
      width="100%" height="280" style="border:0; border-radius: 10px;" allowfullscreen="" loading="lazy">
    </iframe>
    <a class="gmaps-link" target="_blank"
       href="https://www.google.com/maps/search/?api=1&query=Anjung+Floria+Putrajaya">
       Open in Google Maps
    </a>
  </div>
</div>

<!-- 📍 Box Fifteen -->
<div id="modal-fifteen" class="modal">
  <div class="modal-content">
    <button class="close" onclick="closeModal('fifteen')">&times;</button>
    <h2>Box Fifteen</h2>
    <p class="modal-tag status-tag">Temporarily closed</p>
    <p><strong>Address:</strong> 20, Jalan SS 15/8b, Ss 15, 47500 Subang Jaya, Selangor</p>
    <p><strong>Phone:</strong> <a href="tel:0105432234">010-543 2234</a></p>
    <p><strong>Price per person:</strong> RM 20–40</p>
    <iframe 
      src="https://www.google.com/maps/embed/v1/place?key=AIzaSyB9mo6ojcedxXITE4YEKupXj0ZifaaGP7I&q=20+Jalan+SS+15%2F8b+Subang+Jaya"
      width="100%" height="280" style="border:0; border-radius: 10px;" allowfullscreen="" loading="lazy">
    </iframe>
    <a class="gmaps-link" target="_blank"
       href="https://www.google.com/maps/search/?api=1&query=20+Jalan+SS+15%2F8b+Subang+Jaya">
       Open in Google Maps
    </a>
  </div>
</div>

<!-- 📍 Box FZHD -->
<div id="modal-fzhd" class="modal">
  <div class="modal-content">
    <button class="close" onclick="closeModal('fzhd')">&times;</button>
    <h2>Box FZHD</h2>
    <p class="modal-tag">Neighborhood favorite · Family-friendly</p>
    <p><strong>Address:</strong> 53-G, Jln Bazar P U8/P, Bukit Jelutong, 40150 Shah Alam, Selangor</p>
    <p><strong>Phone:</strong> <a href="tel:0105432234">010-543 2234</a></p>
    <p><strong>Hours:</strong> Closes 10 pm</p>
    <p><strong>Price per person:</strong> RM 20–40</p>
    <iframe 
      src="https://www.google.com/maps/embed/v1/place?key=AIzaSyB9mo6ojcedxXITE4YEKupXj0ZifaaGP7I&q=53-G+Jln+Bazar+P+U8%2FP+Shah+Alam"
      width="100%" height="280" style="border:0; border-radius: 10px;" allowfullscreen="" loading="lazy">
    </iframe>
    <a class="gmaps-link" target="_blank"
       href="https://www.google.com/maps/search/?api=1&query=53-G+Jln+Bazar+P+U8%2FP+Shah+Alam">
       Open in Google Maps
    </a>
  </div>
</div>

<?php include("footer.php"); ?>

<!-- ================== STYLES ================== -->
<style>
/* ===== MODERN BRANCHES SECTION ===== */
.branches-section {
    padding: 3.5rem 1.5rem 4rem;
    background: #faf7f3;
}

.branches-header {
    text-align: center;
    max-width: 640px;
    margin: 0 auto 2.5rem;
}

.branches-header h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.branches-header p {
    color: #666;
    font-size: 0.98rem;
}

/* GRID */
.location-grid {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.8rem;
}

/* LOCATION CARD */
.location-item {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 12px 25px rgba(15,15,15,0.08);
    cursor: pointer;
    transition: transform 0.18s ease, box-shadow 0.18s ease, translate 0.18s ease;
    display: flex;
    flex-direction: column;
}

.location-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 40px rgba(0,0,0,0.12);
}

.location-img-wrap {
    aspect-ratio: 4/3;
    overflow: hidden;
}

.location-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.35s ease;
}

.location-item:hover .location-img-wrap img {
    transform: scale(1.06);
}

.location-item h3 {
    margin: 0.9rem 1.1rem 0.25rem;
    font-size: 1.05rem;
}

.location-meta {
    margin: 0 1.1rem 1.1rem;
    font-size: 0.9rem;
    color: #777;
}

/* ===== MODALS ===== */
.modal {
  display: none;
  position: fixed;
  z-index: 999;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.45);
  padding: 1.5rem;
}

.modal-content {
  background-color: #fff;
  margin: auto;
  width: 100%;
  max-width: 640px;
  border-radius: 18px;
  position: relative;
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
  padding: 1.8rem 1.8rem 1.6rem;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content h2 {
  margin-top: 0.2rem;
  margin-bottom: 0.35rem;
}

.modal-tag {
  font-size: 0.9rem;
  color: #777;
  margin-bottom: 0.9rem;
}

.status-tag {
  color: #b94a4a;
}

.gmaps-link {
  display: inline-block;
  margin-top: 0.9rem;
  font-size: 0.9rem;
  text-decoration: none;
  color: #111;
  font-weight: 500;
}

.gmaps-link:hover {
  text-decoration: underline;
}

.close {
  position: absolute;
  right: 16px;
  top: 10px;
  font-size: 24px;
  cursor: pointer;
  border: none;
  background: transparent;
}

/* ===== HOME HERO TITLE ANIMATION ===== */
.home-hero-title {
    font-size: 2.6rem;
    font-weight: 800;
    opacity: 0;
    transform: translateY(18px);
    animation: homeTitleIn 0.7s ease-out forwards;
}

.home-hero-sub {
    max-width: 900px;
    margin: 0.8rem auto 0;
    font-size: 1.05rem;
    opacity: 0;
    transform: translateY(10px);
    animation: homeSubIn 0.8s ease-out forwards;
    animation-delay: 0.2s;
}

.home-hero-sub-secondary {
    animation-delay: 0.35s;
    opacity: 0.9;
}

@keyframes homeTitleIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes homeSubIn {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===== HERO BUTTONS – ALL WHITE ===== */
.hero .hero-buttons {
    margin-top: 1.8rem;
    display: flex;
    justify-content: center;
    gap: 1.2rem;
    flex-wrap: wrap;
}

.hero .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.7rem;
    border-radius: 10px;
    border: 1px solid #111;
    background: #fff;
    color: #111;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    transition: background 0.25s ease, color 0.25s ease,
                transform 0.15s ease, box-shadow 0.15s ease;
}

.hero .btn:hover {
    background: #111;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.18);
}

/* ===== HOMEPAGE BLACK HERO SECTION ===== */
.home-hero-black {
    background: #000;
    color: #fff;
    padding: 4rem 2rem;
    text-align: center;
}

.home-hero-black .home-hero-sub,
.home-hero-black .home-hero-sub-secondary {
    color: #ddd;
}

.home-hero-black .home-hero-title {
    color: #fff;
}

/* Buttons on black section */
.home-hero-black .btn {
    background: #fff;
    border: 1px solid #fff;
    color: #000;
}

.home-hero-black .btn:hover {
    background: #111;
    color: #fff;
    border-color: #fff;
}

/* Small screen tweaks */
@media (max-width: 600px) {
    .home-hero-title {
        font-size: 2.1rem;
    }
    .branches-section {
        padding-inline: 1.1rem;
    }
}
</style>

<script>
function openModal(branch) {
  document.getElementById("modal-" + branch).style.display = "block";
}

function closeModal(branch) {
  document.getElementById("modal-" + branch).style.display = "none";
}

// If you still use slideshow somewhere on page
let slideIndex = 0;
function showSlides() {
  const slides = document.getElementsByClassName("slide");
  if (!slides.length) return;
  for (let i = 0; i < slides.length; i++) {
    slides[i].style.display = "none";
  }
  slideIndex++;
  if (slideIndex > slides.length) { slideIndex = 1; }
  slides[slideIndex - 1].style.display = "block";
  setTimeout(showSlides, 4000);
}
showSlides();
</script>
