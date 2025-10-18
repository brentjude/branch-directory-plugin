<?php
/**
 * Shortcode: [branch_map]
 * Display branches on a map + grid list with filters and modal
 * Attributes:
 *  - per_page: Number of branches per page (default: 9)
 *  - show_map: Show/hide map by default (default: true)
 */
function bm_branch_map_shortcode($atts) {
    $atts = shortcode_atts([
        'per_page' => 9,
        'show_map' => 'true'
    ], $atts);
    
    ob_start();
    
    // Get URL parameters for country/city selection
    $selected_country = isset($_GET['country']) ? intval($_GET['country']) : '';
    $selected_city = isset($_GET['city']) ? intval($_GET['city']) : '';
    $current_page = isset($_GET['branch_page']) ? max(1, intval($_GET['branch_page'])) : 1;
    
    // Get all countries and cities
    $countries = get_terms(['taxonomy' => 'branch_country', 'hide_empty' => true]);
    $cities = get_terms(['taxonomy' => 'branch_city', 'hide_empty' => true]);
    $country_count = is_array($countries) ? count($countries) : 0;
    
    // Query args with pagination
    $query_args = [
        'post_type' => 'branch',
        'posts_per_page' => intval($atts['per_page']),
        'paged' => $current_page
    ];
    
    // Add tax query if country or city selected
    $tax_query = [];
    if ($selected_country) {
        $tax_query[] = [
            'taxonomy' => 'branch_country',
            'field' => 'term_id',
            'terms' => $selected_country
        ];
    }
    if ($selected_city) {
        $tax_query[] = [
            'taxonomy' => 'branch_city',
            'field' => 'term_id',
            'terms' => $selected_city
        ];
    }
    if (!empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }
    
    $branches = new WP_Query($query_args);
    
    // Get all branches for map (not paginated)
    $all_branches = new WP_Query(['post_type' => 'branch', 'posts_per_page' => -1]);
    ?>

    <!-- Micromodal CSS -->
    <link rel="stylesheet" href="https://unpkg.com/micromodal/dist/micromodal.css">

    <style>
      /* Filter Section */
      .branch-filters {
          display: flex;
          gap: 20px;
          margin-bottom: 30px;
          flex-wrap: wrap;
          align-items: flex-end;
      }
      
      .filter-group {
          display: flex;
          flex-direction: column;
          gap: 8px;
      }
      
      .filter-group.search-group {
          flex: 1;
          min-width: 250px;
      }
      
      .filter-group label {
          font-size: 14px;
          font-weight: 600;
          color: #330A48;
          margin-bottom: 0;
      }
      
      .branch-filters input,
      .branch-filters select {
          padding: 12px 16px;
          border: 2px solid #ddd;
          border-radius: 6px;
          font-size: 15px;
          transition: all 0.3s ease;
          background: white;
      }
      
      .branch-filters input:focus,
      .branch-filters select:focus {
          outline: none;
          border-color: #330A48;
          box-shadow: 0 0 0 3px rgba(51, 10, 72, 0.1);
      }
      
      .branch-filters input {
          width: 100%;
      }
      
      .branch-filters select {
          min-width: 180px;
          cursor: pointer;
          appearance: none;
          background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23330A48' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
          background-repeat: no-repeat;
          background-position: right 12px center;
          padding-right: 40px;
      }
      
      .branch-filters input::placeholder {
          color: #999;
      }

      /* Grid - 3 columns */
      .branch-grid {
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          gap: 20px;
          margin-top: 20px;
          margin-bottom: 30px;
      }
      @media (max-width: 992px) {
          .branch-grid {
              grid-template-columns: repeat(2, 1fr);
          }
          .branch-filters {
              gap: 15px;
          }
          .filter-group.search-group {
              min-width: 100%;
          }
      }
      @media (max-width: 576px) {
          .branch-grid {
              grid-template-columns: 1fr;
          }
          .filter-group {
              width: 100%;
          }
          .branch-filters select {
              width: 100%;
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

      /* Pagination */
      .branch-pagination {
          display: flex;
          justify-content: center;
          align-items: center;
          gap: 10px;
          margin: 30px 0;
      }
      .branch-pagination a,
      .branch-pagination span {
          padding: 8px 15px;
          border: 1px solid #ddd;
          border-radius: 4px;
          text-decoration: none;
          color: #330A48;
          transition: all 0.3s;
      }
      .branch-pagination a:hover {
          background: #330A48;
          color: white;
          border-color: #330A48;
      }
      .branch-pagination .current {
          background: #330A48;
          color: white;
          border-color: #330A48;
      }

      /* Map Section */
      .branch-map-section {
          margin-top: 30px;
      }
      .map-toggle-btn {
          background: #330A48;
          color: white;
          border: none;
          padding: 12px 24px;
          border-radius: 4px;
          cursor: pointer;
          font-size: 16px;
          margin-bottom: 15px;
          transition: background 0.3s;
      }
      .map-toggle-btn:hover {
          background: #4a0e68;
      }
      .map-container {
          display: <?php echo strtolower($atts['show_map']) === 'true' ? 'block' : 'none'; ?>;
          margin-top: 15px;
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
        <div class="filter-group search-group">
            <label for="branch-search">Search Branches</label>
            <input type="text" id="branch-search" placeholder="Search by name or address...">
        </div>
        
        <?php if ($country_count > 1): ?>
        <div class="filter-group">
            <label for="country-filter">Country</label>
            <select id="country-filter">
                <option value="">All Countries</option>
                <?php foreach($countries as $country): ?>
                    <option value="<?php echo esc_attr($country->term_id); ?>" 
                            <?php selected($selected_country, $country->term_id); ?>>
                        <?php echo esc_html($country->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        
        <div class="filter-group">
            <label for="city-filter">City</label>
            <select id="city-filter">
                <option value="">All Cities</option>
                <?php foreach($cities as $city): ?>
                    <option value="<?php echo esc_attr($city->term_id); ?>" 
                            <?php selected($selected_city, $city->term_id); ?>>
                        <?php echo esc_html($city->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Branches Grid -->
    <div class="branch-grid">
        <?php 
        if ($branches->have_posts()) {
            while($branches->have_posts()): $branches->the_post(); 
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
            <?php 
            endwhile;
        } else {
            echo '<p style="grid-column: 1/-1; text-align: center; padding: 40px;">No branches found.</p>';
        }
        ?>
    </div>

    <!-- Pagination -->
    <?php if ($branches->max_num_pages > 1): ?>
    <div class="branch-pagination">
        <?php
        $base_url = remove_query_arg('branch_page');
        if ($selected_country) $base_url = add_query_arg('country', $selected_country, $base_url);
        if ($selected_city) $base_url = add_query_arg('city', $selected_city, $base_url);
        
        // Previous
        if ($current_page > 1) {
            echo '<a href="' . esc_url(add_query_arg('branch_page', $current_page - 1, $base_url)) . '">« Previous</a>';
        }
        
        // Page numbers
        for ($i = 1; $i <= $branches->max_num_pages; $i++) {
            if ($i == $current_page) {
                echo '<span class="current">' . $i . '</span>';
            } else {
                echo '<a href="' . esc_url(add_query_arg('branch_page', $i, $base_url)) . '">' . $i . '</a>';
            }
        }
        
        // Next
        if ($current_page < $branches->max_num_pages) {
            echo '<a href="' . esc_url(add_query_arg('branch_page', $current_page + 1, $base_url)) . '">Next »</a>';
        }
        ?>
    </div>
    <?php endif; ?>

    <!-- Map Section (Below Cards) -->
    <div class="branch-map-section">
        <button class="map-toggle-btn" id="map-toggle">
            <span id="map-toggle-text"><?php echo strtolower($atts['show_map']) === 'true' ? 'Hide' : 'Show'; ?> Map</span>
        </button>
        <div class="map-container" id="map-container">
            <div id="branch-map" style="height:400px; width:100%;"></div>
        </div>
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

    // Branch data for modal and filtering
    const branchData = {};
    <?php 
    $all_branches->rewind_posts();
    while($all_branches->have_posts()): $all_branches->the_post(); 
        $contact = get_post_meta(get_the_ID(), '_branch_contact', true);
        $address = get_post_meta(get_the_ID(), '_branch_address', true);
        $lat = get_post_meta(get_the_ID(), '_branch_lat', true);
        $lng = get_post_meta(get_the_ID(), '_branch_lng', true);
        $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium') : plugin_dir_url(__DIR__) . 'assets/no-image.png';
        
        $branch_countries = wp_get_post_terms(get_the_ID(), 'branch_country', ['fields' => 'ids']);
        $branch_cities = wp_get_post_terms(get_the_ID(), 'branch_city', ['fields' => 'ids']);
    ?>
    branchData[<?php echo get_the_ID(); ?>] = {
        title: "<?php echo addslashes(get_the_title()); ?>",
        address: "<?php echo addslashes($address); ?>",
        contact: "<?php echo addslashes($contact); ?>",
        image: "<?php echo $image_url; ?>",
        lat: "<?php echo esc_js($lat); ?>",
        lng: "<?php echo esc_js($lng); ?>",
        countries: [<?php echo !empty($branch_countries) ? implode(',', $branch_countries) : ''; ?>],
        cities: [<?php echo !empty($branch_cities) ? implode(',', $branch_cities) : ''; ?>]
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

    // Map toggle functionality
    document.getElementById('map-toggle').addEventListener('click', function() {
        const mapContainer = document.getElementById('map-container');
        const toggleText = document.getElementById('map-toggle-text');
        
        if (mapContainer.style.display === 'none') {
            mapContainer.style.display = 'block';
            toggleText.textContent = 'Hide Map';
            // Initialize map if not already done
            if (!window.branchMapInitialized) {
                initializeMap();
            }
        } else {
            mapContainer.style.display = 'none';
            toggleText.textContent = 'Show Map';
        }
    });

    // Search and filter functionality
    const searchInput = document.getElementById('branch-search');
    const countryFilter = document.getElementById('country-filter');
    const cityFilter = document.getElementById('city-filter');
    const branchCards = document.querySelectorAll('.branch-card');

    function filterBranches() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCountry = countryFilter ? countryFilter.value : '';
        const selectedCity = cityFilter.value;

        let visibleBranchIds = [];

        branchCards.forEach(card => {
            const branchId = card.dataset.branchId;
            const title = card.dataset.title.toLowerCase();
            const address = card.dataset.address.toLowerCase();
            const country = card.dataset.country;
            const city = card.dataset.city;

            const matchesSearch = title.includes(searchTerm) || address.includes(searchTerm);
            const matchesCountry = !selectedCountry || country.split(',').includes(selectedCountry);
            const matchesCity = !selectedCity || city.split(',').includes(selectedCity);

            if (matchesSearch && matchesCountry && matchesCity) {
                card.style.display = 'block';
                visibleBranchIds.push(branchId);
            } else {
                card.style.display = 'none';
            }
        });

        // Update map markers
        if (window.branchMapInitialized) {
            updateMapMarkers(visibleBranchIds);
        }
    }

    // Real-time search filtering
    searchInput.addEventListener('input', filterBranches);

    // Filter with URL parameters for dropdowns
    if (countryFilter) {
        countryFilter.addEventListener('change', function() {
            updateURLFilters();
        });
    }
    
    cityFilter.addEventListener('change', function() {
        updateURLFilters();
    });

    function updateURLFilters() {
        const url = new URL(window.location);
        const country = countryFilter ? countryFilter.value : '';
        const city = cityFilter.value;
        
        // Update URL parameters
        if (country) {
            url.searchParams.set('country', country);
        } else {
            url.searchParams.delete('country');
        }
        
        if (city) {
            url.searchParams.set('city', city);
        } else {
            url.searchParams.delete('city');
        }
        
        // Remove page parameter when filtering
        url.searchParams.delete('branch_page');
        
        // Reload with new parameters
        window.location.href = url.toString();
    }

    // Initialize map
    let branchMap = null;
    let allMarkers = {};
    window.branchMapInitialized = false;

    function initializeMap() {
        if (window.branchMapInitialized) return;
        
        branchMap = L.map('branch-map').setView([14.5995, 120.9842], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(branchMap);

        // Create all markers
        <?php 
        $all_branches->rewind_posts();
        while($all_branches->have_posts()): $all_branches->the_post(); 
            $lat = get_post_meta(get_the_ID(), '_branch_lat', true);
            $lng = get_post_meta(get_the_ID(), '_branch_lng', true);
            $branch_id = get_the_ID();
            
            if ($lat && $lng) {
                ?>
                allMarkers[<?php echo $branch_id; ?>] = L.marker([<?php echo esc_js($lat); ?>, <?php echo esc_js($lng); ?>])
                  .bindTooltip("<?php echo addslashes(get_the_title()); ?>", { 
                      permanent: true, 
                      direction: "right", 
                      offset: [10, 0] 
                  })
                  .on('click', function() {
                      openBranchModal(<?php echo $branch_id; ?>);
                  });
                <?php
            }
        endwhile; wp_reset_postdata();
        ?>

        // Add all markers initially
        const allBranchIds = Object.keys(branchData);
        updateMapMarkers(allBranchIds);

        window.branchMapInitialized = true;
    }

    function updateMapMarkers(visibleBranchIds) {
        if (!branchMap) return;

        const markers = [];

        // Remove all markers first
        Object.keys(allMarkers).forEach(branchId => {
            if (branchMap.hasLayer(allMarkers[branchId])) {
                branchMap.removeLayer(allMarkers[branchId]);
            }
        });

        // Add only visible markers
        visibleBranchIds.forEach(branchId => {
            if (allMarkers[branchId]) {
                allMarkers[branchId].addTo(branchMap);
                markers.push(allMarkers[branchId]);
            }
        });

        // Fit map to visible markers
        if (markers.length > 0) {
            const group = L.featureGroup(markers);
            branchMap.fitBounds(group.getBounds(), {
                padding: [50, 50],
                maxZoom: 14
            });
        }
    }

    // Initialize map if visible by default
    <?php if (strtolower($atts['show_map']) === 'true'): ?>
    document.addEventListener("DOMContentLoaded", function() {
        initializeMap();
        
        // Run initial filter to show only current page branches
        filterBranches();
    });
    <?php endif; ?>
    </script>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('branch_map', 'bm_branch_map_shortcode');