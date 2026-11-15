<?php
$currentPage = 'about';
$pageTitle = "About Us — Mendly";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/about.css">
</head>

<body>

  <?php include("includes/header.php"); ?>

  <main>
    <!-- HERO -->
    <section class="hero">
      <span class="eyebrow">About Us</span>
      <h1>
        Skilled hands,<br>
        <span class="mark">
          meaningful <em>work.</em>
        </span>
      </h1>

      <div class="hero-grid">
        <div class="hero-desc">
          <p>
            Mendly connects skilled experts, including artisans and
            service professionals, with customers looking for quality
            services. Experts can showcase their skills and services,
            while customers can easily find the right expert for their needs.
          </p>
        </div>

        <div class="hero-meta">
          <div class="stat">
            <div class="n">100+</div>
            <div class="l">EXPERTS JOINED</div>
          </div>

          <div class="stat">
            <div class="n">50+</div>
            <div class="l">SERVICES OFFERED</div>
          </div>

          <div class="stat">
            <div class="n">24/7</div>
            <div class="l">SERVICE DISCOVERY</div>
          </div>
        </div>
      </div>
    </section>

    <!-- BODY COPY -->
    <div class="body-copy">
      <div class="label">Our story</div>

      <div class="text">
        <p>
          <strong>
            Mendly was created with a simple purpose:
            to help experts turn their skills into opportunities
          </strong>
          We believe everyone has skills and experience worth sharing,
          but many experts struggle to reach customers and promote their services.
        </p>

        <p>
          Mendly brings experts and customers together on one platform. Experts
          can create a profile, showcase their work, and offer their services.
          Customers can browse services and find the right expert for their needs.
        </p>

        <p>
          Mendly is more than a service platform. We want to support experts,
          including artisans and skilled professionals, while making it easier
          for customers to find reliable services.
        </p>
      </div>
    </div>

    <!-- MISSION & VISION -->
    <section class="values" id="values">

      <div class="values-head">
        <h2>
          Empowering experts. Connecting skills. Creating opportunities.
        </h2>
      </div>

      <div class="value-grid">

        <div class="value-card">
          <span class="index">01 / MISSION</span>
          <h3>Empower every expert</h3>
          <p>
            Help experts showcase their skills,
            offer their services, reach customers,
            and grow their opportunities.
          </p>
        </div>

        <div class="value-card">
          <span class="index">02 / VISION</span>
          <h3>Make every skill discoverable</h3>
          <p>
            Become a trusted platform where every expert has
            the opportunity to be discovered and every customer can easily
            find the right expert for their needs.
          </p>
        </div>

        <div class="value-card">
          <span class="index">03 / COMMUNITY</span>
          <h3>Connect experts and customers</h3>
          <p>
            Bring experts and customers together, making it easier for
            experts to share their skills and for customers to find the services they need.
          </p>
        </div>

        <div class="value-card">
          <span class="index">04 / TRUST</span>
          <h3>Build meaningful connections</h3>
          <p>
            Create a trusted platform where experts can clearly present their services
            and customers can make informed choices.
          </p>
        </div>

      </div>
    </section>
  </main>

  <script src="assets/js/about.js"></script>

  <?php include("includes/footer.php"); ?>

</body>

</html>