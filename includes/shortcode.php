<?php
/**
 * Shortcode: [branch_map]
 * Display branches on a map + grid list with filters and modal
 */
function bm_branch_map_shortcode() {
    ob_start();
    
    // Get all countries and cities
    $countries = get_terms(['taxonomy' => 'branch_country', 'hide_empty' => true]);
    $cities = get_terms(['taxonomy' => 'branch_city', 'hide_empty' => true]);
    $country_count = is_array($countries) ? count($countries) : 0;
    
    $branches = new WP_Query(['post_type' => 'branch', 'posts_per_page' => -1]);
    ?>

    <!-- Micromodal CSS -->
    <link rel="stylesheet" href="https://unpkg.com/micromodal/dist/micromodal.css">

    <style>
      /* Filter Section */
      .branch-filters {
          display: flex;
          gap: 15px;
          margin-bottom: 20px;
          flex-wrap: wrap;
          align-items: center;
      }
      .branch-filters input,
      .branch-filters select {
          padding: 10px;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 14px;
      }
      .branch-filters input {
          flex: 1;
          min-width: 200px;
      }
      .branch-filters select {
          min-width: 150px;
      }

      /* Grid - 3 columns */
      .branch-grid {
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          gap: 20px;
          margin-top: 20px;
      }
      @media (max-width: 992px) {
          .branch-grid {
              grid-template-columns: repeat(2, 1fr);
          }
      }
      @media (max-width: 576px) {
          .branch-grid {
              grid-template-columns: 1fr;
          }
      }

      .branch-card {
          border: 1px solid #ddd;
          border-radius: 8px;
          padding: 0px;
          background: #330A48;
          text-align: center;
          box-shadow: 0 2px 5px rgba(0,0,0,0.1);
          cursor: pointer;
          transition: transform 0.2s;
      }
      .branch-card:hover {
          transform: translateY(-5px);
          box-shadow: 0 4px 10px rgba(0,0,0,0.2);
      }
      .branch-image {
          width: 100%;
          height: 200px;
          background-size: cover;
          background-position: center;
          border-radius: 8px 8px 0 0;
      }
      .branch-info {
          padding: 0px 20px 20px 20px;
      }
      .branch-card h3 {
          margin: 5px 0;
          font-size: 1.2em;
          color: white;
      }
      .branch-card p {
          margin: 3px 0;
          font-size: 0.9em;
          color: white;
      }

      /* Tooltip styles */
      .leaflet-tooltip {
          background: #330A48;
          color: #fff;
          border-radius: 4px;
          padding: 4px 8px;
          font-size: 12px;
          font-weight: bold;
          border: none;
          box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      }

      /* Modal Styles */
      .modal {
          display: none;
      }
      .modal.is-open {
          display: block;
      }
      .modal__overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0,0,0,0.6);
          display: flex;
          justify-content: center;
          align-items: center;
          z-index: 10000;
      }
      .modal__container {
          background-color: #fff;
          padding: 30px;
          max-width: 600px;
          max-height: 90vh;
          border-radius: 12px;
          overflow-y: auto;
          box-shadow: 0 10px 40px rgba(0,0,0,0.3);
          position: relative;
      }
      .modal__header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 20px;
      }
      .modal__title {
          margin: 0;
          font-size: 1.5em;
          color: #330A48;
      }
      .modal__close {
          background: transparent;
          border: none;
          font-size: 28px;
          cursor: pointer;
          color: #999;
      }
      .modal__close:hover {
          color: #330A48;
      }
      .modal__content img {
          width: 100%;
          border-radius: 8px;
          margin-bottom: 15px;
      }
      .modal__content p {
          margin: 10px 0;
          line-height: 1.6;
      }
      .modal__content strong {
          color: #330A48;
      }
    </style>

    <!-- Filters -->
    <div class="branch-filters">
        <input type="text" id="branch-search" placeholder="Search branches...">
        <?php if ($country_count > 1): ?>
        <select id="country-filter">
            <option value="">All Countries</option>
            <?php foreach($countries as $country): ?>
                <option value="<?php echo esc_attr($country->term_id); ?>">
                    <?php echo esc_html($country->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select id="city-filter">
            <option value="">All Cities</option>
            <?php foreach($cities as $city): ?>
                <option value="<?php echo esc_attr($city->term_id); ?>" 
                        data-country="<?php echo esc_attr(get_term_meta($city->term_id, 'parent_country', true)); ?>">
                    <?php echo esc_html($city->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Map -->
    <div id="branch-map" style="height:400px; width:100%;"></div>

    <!-- Branches Grid -->
    <div class="branch-grid">
        <?php while($branches->have_posts()): $branches->the_post(); 
            $contact   = get_post_meta(get_the_ID(), '_branch_contact', true);
            $address   = get_post_meta(get_the_ID(), '_branch_address', true);
            $lat       = get_post_meta(get_the_ID(), '_branch_lat', true);
            $lng       = get_post_meta(get_the_ID(), '_branch_lng', true);
            
            $branch_countries = wp_get_post_terms(get_the_ID(), 'branch_country', ['fields' => 'ids']);
            $branch_cities = wp_get_post_terms(get_the_ID(), 'branch_city', ['fields' => 'ids']);
            $country_ids = !empty($branch_countries) ? implode(',', $branch_countries) : '';
            $city_ids = !empty($branch_cities) ? implode(',', $branch_cities) : '';
            
            $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium') : plugin_dir_url(__DIR__) . 'assets/no-image.png';
        ?>
            <div class="branch-card" 
                 data-branch-id="<?php echo get_the_ID(); ?>"
                 data-title="<?php echo esc_attr(get_the_title()); ?>"
                 data-address="<?php echo esc_attr($address); ?>"
                 data-contact="<?php echo esc_attr($contact); ?>"
                 data-lat="<?php echo esc_attr($lat); ?>"
                 data-lng="<?php echo esc_attr($lng); ?>"
                 data-country="<?php echo esc_attr($country_ids); ?>"
                 data-city="<?php echo esc_attr($city_ids); ?>"
                 onclick="openBranchModal(<?php echo get_the_ID(); ?>)">
                <div class="branch-image" style="background-image: url('<?php echo esc_url($image_url); ?>');"></div>
                <div class="branch-info">
                    <h3><?php the_title(); ?></h3>
                    <p><strong>Address:</strong> <?php echo esc_html($address); ?></p>
                    <p><strong>Contact:</strong> <?php echo esc_html($contact); ?></p>
                </div>
            </div>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>

    <!-- Modal Template -->
    <div class="modal micromodal-slide" id="branch-modal" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="branch-modal-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="branch-modal-title">Branch Details</h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close>&times;</button>
                </header>
                <div class="modal__content" id="branch-modal-content">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
    
    <script>
    // Initialize Micromodal
    MicroModal.init({
        disableScroll: true,
        awaitCloseAnimation: true
    });

    // Branch data for modal
    const branchData = {};
    <?php 
    $branches->rewind_posts();
    while($branches->have_posts()): $branches->the_post(); 
        $contact = get_post_meta(get_the_ID(), '_branch_contact', true);
        $address = get_post_meta(get_the_ID(), '_branch_address', true);
        $lat = get_post_meta(get_the_ID(), '_branch_lat', true);
        $lng = get_post_meta(get_the_ID(), '_branch_lng', true);
        $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium') : plugin_dir_url(__DIR__) . 'assets/no-image.png';
    ?>
    branchData[<?php echo get_the_ID(); ?>] = {
        title: "<?php echo addslashes(get_the_title()); ?>",
        address: "<?php echo addslashes($address); ?>",
        contact: "<?php echo addslashes($contact); ?>",
        image: "<?php echo $image_url; ?>",
        lat: "<?php echo esc_js($lat); ?>",
        lng: "<?php echo esc_js($lng); ?>"
    };
    <?php endwhile; wp_reset_postdata(); ?>

    // Open branch modal
    function openBranchModal(branchId) {
        const branch = branchData[branchId];
        if (!branch) return;

        const content = `
            <img src="${branch.image}" alt="${branch.title}">
            <p><strong>Branch:</strong> ${branch.title}</p>
            <p><strong>Address:</strong> ${branch.address}</p>
            <p><strong>Contact:</strong> ${branch.contact}</p>
            ${branch.lat && branch.lng ? `<p><strong>Location:</strong> ${branch.lat}, ${branch.lng}</p>` : ''}
        `;
        
        document.getElementById('branch-modal-content').innerHTML = content;
        MicroModal.show('branch-modal');
    }

    document.addEventListener("DOMContentLoaded", function() {
        var map = L.map('branch-map').setView([14.5995, 120.9842], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var markers = [];
        var markerObjects = {};

        <?php 
        $branches->rewind_posts();
        while($branches->have_posts()): $branches->the_post(); 
            $lat = get_post_meta(get_the_ID(), '_branch_lat', true);
            $lng = get_post_meta(get_the_ID(), '_branch_lng', true);
            $contact = get_post_meta(get_the_ID(), '_branch_contact', true);
            $address = get_post_meta(get_the_ID(), '_branch_address', true);
            $branch_id = get_the_ID();
            
            if ($lat && $lng) {
                $photo = has_post_thumbnail() ? wp_get_attachment_image_url(get_post_thumbnail_id(), 'thumbnail') : plugin_dir_url(__DIR__) . 'assets/no-image.png';
                ?>
                var marker = L.marker([<?php echo esc_js($lat); ?>, <?php echo esc_js($lng); ?>])
                  .addTo(map)
                  .bindTooltip("<?php echo addslashes(get_the_title()); ?>", { 
                      permanent: true, 
                      direction: "right", 
                      offset: [10, 0] 
                  })
                  .on('click', function() {
                      openBranchModal(<?php echo $branch_id; ?>);
                  });

                markers.push(marker);
                markerObjects[<?php echo $branch_id; ?>] = marker;
                <?php
            }
        endwhile; wp_reset_postdata();
        ?>

        // Auto fit map to markers
        if (markers.length > 0) {
            var group = L.featureGroup(markers);
            map.fitBounds(group.getBounds(), {
                padding: [50, 50],
                maxZoom: 14
            });
        }

        // Filter functionality
        const searchInput = document.getElementById('branch-search');
        const countryFilter = document.getElementById('country-filter');
        const cityFilter = document.getElementById('city-filter');
        const branchCards = document.querySelectorAll('.branch-card');

        function filterBranches() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCountry = countryFilter ? countryFilter.value : '';
            const selectedCity = cityFilter.value;

            branchCards.forEach(card => {
                const title = card.dataset.title.toLowerCase();
                const address = card.dataset.address.toLowerCase();
                const country = card.dataset.country;
                const city = card.dataset.city;

                const matchesSearch = title.includes(searchTerm) || address.includes(searchTerm);
                const matchesCountry = !selectedCountry || country.split(',').includes(selectedCountry);
                const matchesCity = !selectedCity || city.split(',').includes(selectedCity);

                if (matchesSearch && matchesCountry && matchesCity) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });

            // Update visible markers
            Object.keys(markerObjects).forEach(branchId => {
                const card = document.querySelector(`[data-branch-id="${branchId}"]`);
                if (card && card.style.display === 'none') {
                    map.removeLayer(markerObjects[branchId]);
                } else if (card && card.style.display !== 'none') {
                    if (!map.hasLayer(markerObjects[branchId])) {
                        markerObjects[branchId].addTo(map);
                    }
                }
            });
        }

        searchInput.addEventListener('input', filterBranches);
        if (countryFilter) {
            countryFilter.addEventListener('change', filterBranches);
        }
        cityFilter.addEventListener('change', filterBranches);
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('branch_map', 'bm_branch_map_shortcode');