<main id="main-content">
    <div class="main-container">

        <!-- Hero: what Mendly is, plus a quick search into Browse Services -->
        <section class="home-hero">
            <div class="hero-copy">
                <p class="hero-eyebrow">Malaysia's craft repair marketplace</p>
                <h1>Repair, don't replace.</h1>
                <p class="hero-lead">
                    Find skilled local artisans to restore the things you love — from heirloom
                    cheongsams and childhood teddy bears to vintage books and leather bags.
                </p>

                <form class="hero-search" action="browse-services.php" method="get" role="search">
                    <label for="heroSearch" class="visually-hidden">Search repair services</label>
                    <input id="heroSearch" type="search" name="search" placeholder="What needs fixing? e.g. bag, shoes, doll">
                    <button type="submit">Search</button>
                </form>

                <div class="hero-actions">
                    <a href="browse-services.php" class="hero-btn hero-btn-solid">Browse all services</a>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register-expert.php" class="hero-btn hero-btn-outline">Join as a repair expert</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ================= CATEGORY 1: TEXTILES & APPAREL ================= -->
        <section class="category-section">
            <div class="category-banner">
                <div class="banner-content">
                    <h2>Textiles & Apparel Restoration</h2>
                    <p>Expert care for everyday garments, heritage traditional wear, and home fabrics.</p>
                </div>
            </div>

            <div class="cards-grid">
                <!-- Sub 1.1 -->
                <div class="service-card" onclick="location.href='browse-services.php?category[]=1';">
                    <div class="card-img-wrapper">
                        <img src="assets/images/services/clothing-alteration.jpg" alt="General Clothing Alteration & Tailoring" class="card-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="img-placeholder" style="display:none;">Clothing Alteration & Tailoring</div>
                    </div>
                    <div class="card-body">
                        <h3>General Clothing Alteration & Tailoring</h3>
                        <p class="card-desc">Resizing, zipper replacement, hem adjustments, and seamless garment repair.</p>
                    </div>
                </div>

                <!-- Sub 1.2 -->
                <div class="service-card" onclick="location.href='browse-services.php?category[]=2';">
                    <div class="card-img-wrapper">
                        <img src="assets/images/services/traditional-wear.jpg" alt="Cultural & Traditional Wear Restoration" class="card-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="img-placeholder" style="display:none;">Cultural & Traditional Wear</div>
                    </div>
                    <div class="card-body">
                        <h3>Cultural & Traditional Wear Restoration</h3>
                        <p class="card-desc">Delicate embroidery fixing and fitting for Cheongsam, Baju Melayu, and heritage attire.</p>
                    </div>
                </div>

                <!-- Sub 1.3 -->
                <div class="service-card" onclick="location.href='browse-services.php?category[]=3';">
                    <div class="card-img-wrapper">
                        <img src="assets/images/services/home-textiles.jpg" alt="Home Textiles & Furnishing Repair" class="card-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="img-placeholder" style="display:none;">Home Textiles & Furnishing</div>
                    </div>
                    <div class="card-body">
                        <h3>Home Textiles & Furnishing Repair</h3>
                        <p class="card-desc">Restoration for curtains, carpets, cushions, and sofa covers to extend their lifespan.</p>
                    </div>
                </div>
            </div>

            <div class="more-link-wrapper">
                <a href="browse-services.php?category[]=1&category[]=2&category[]=3" class="more-link">More Services &rarr;</a>
            </div>
        </section>


        <!-- ================= CATEGORY 2: TOYS & MEMORABILIA ================= -->
        <section class="category-section">
            <div class="category-banner">
                <div class="banner-content">
                    <h2>Toys & Memorabilia Conservation</h2>
                    <p>Preserving cherished childhood memories, vintage plushies, antique books, and paper art.</p>
                </div>
            </div>

            <div class="cards-grid">
                <!-- Sub 2.1 -->
                <div class="service-card" onclick="location.href='browse-services.php?category[]=4';">
                    <div class="card-img-wrapper">
                        <img src="assets/images/services/plushie-doctor.jpg" alt="Plushie & Doll Doctor Services" class="card-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="img-placeholder" style="display:none;">Plushie & Doll Doctor</div>
                    </div>
                    <div class="card-body">
                        <h3>Plushie & Doll Doctor Services</h3>
                        <p class="card-desc">Cotton re-stuffing, limb re-attachment, cleaning, and delicate seam repairs for stuffed toys.</p>
                    </div>
                </div>

                <!-- Sub 2.2 -->
                <div class="service-card" onclick="location.href='browse-services.php?category[]=5';">
                    <div class="card-img-wrapper">
                        <img src="assets/images/services/book-restoration.jpg" alt="Second-hand & Antique Book Restoration" class="card-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="img-placeholder" style="display:none;">Antique Book Restoration</div>
                    </div>
                    <div class="card-body">
                        <h3>Second-hand & Antique Book Restoration</h3>
                        <p class="card-desc">Spine re-binding, damaged cover restoration, and page reinforcement for old reads.</p>
                    </div>
                </div>

                <!-- Sub 2.3 -->
                <div class="service-card" onclick="location.href='browse-services.php?category[]=6';">
                    <div class="card-img-wrapper">
                        <img src="assets/images/services/paper-memorabilia.jpg" alt="Paper Memorabilia & Artwork Conservation" class="card-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="img-placeholder" style="display:none;">Paper Memorabilia & Artwork</div>
                    </div>
                    <div class="card-body">
                        <h3>Paper Memorabilia & Artwork Conservation</h3>
                        <p class="card-desc">Restoring old photographs, vintage certificates, calligraphy, and historic paper documents.</p>
                    </div>
                </div>
            </div>

            <div class="more-link-wrapper">
                <a href="browse-services.php?category[]=4&category[]=5&category[]=6" class="more-link">More Services &rarr;</a>
            </div>
        </section>


        <!-- ================= CATEGORY 3: LEATHER & ACCESSORIES ================= -->
        <section class="category-section">
            <div class="category-banner">
                <div class="banner-content">
                    <h2>Leather Goods & Accessories Repair</h2>
                    <p>Professional reconditioning and craftsmanship for leather bags, shoes, and luxury accessories.</p>
                </div>
            </div>

            <div class="cards-grid cards-grid-two">
                <!-- Sub 3.1 -->
                <div class="service-card" onclick="location.href='browse-services.php?category[]=7';">
                    <div class="card-img-wrapper">
                        <img src="assets/images/services/bag-refurbishment.jpg" alt="Bag & Leather Goods Refurbishment" class="card-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="img-placeholder" style="display:none;">Bag & Leather Refurbishment</div>
                    </div>
                    <div class="card-body">
                        <h3>Bag & Leather Goods Refurbishment</h3>
                        <p class="card-desc">Color restoration, leather deep cleaning, edge paint repair, and hardware polish.</p>
                    </div>
                </div>

                <!-- Sub 3.2 -->
                <div class="service-card" onclick="location.href='browse-services.php?category[]=8';">
                    <div class="card-img-wrapper">
                        <img src="assets/images/services/footwear-repair.jpg" alt="Footwear Repair & Sole Replacement" class="card-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="img-placeholder" style="display:none;">Footwear Repair & Sole Replacement</div>
                    </div>
                    <div class="card-body">
                        <h3>Footwear Repair & Sole Replacement</h3>
                        <p class="card-desc">Resoling, heel tip restoration, leather stitching, and protective rubber sole addition.</p>
                    </div>
                </div>
            </div>
            <div class="more-link-wrapper">
                <a href="browse-services.php?category[]=7&category[]=8" class="more-link">More Services &rarr;</a>
            </div>

        </section>

    </div>
</main>