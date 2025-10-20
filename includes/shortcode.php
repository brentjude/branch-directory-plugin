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
    
    // Get URL parameters for region/province selection
    $selected_region = isset($_GET['region']) ? intval($_GET['region']) : '';
    $selected_province = isset($_GET['province']) ? intval($_GET['province']) : '';
    $current_page = isset($_GET['branch_page']) ? max(1, intval($_GET['branch_page'])) : 1;
    
    // Get all regions and provinces
    $regions = get_terms(['taxonomy' => 'branch_region', 'hide_empty' => true]);
    
    // Get provinces - filter by region if selected
    $province_args = ['taxonomy' => 'branch_province', 'hide_empty' => true];
    if ($selected_region) {
        // Get all provinces
        $all_provinces_temp = get_terms(['taxonomy' => 'branch_province', 'hide_empty' => true]);
        $provinces = [];
        foreach($all_provinces_temp as $prov) {
            $parent_region = get_term_meta($prov->term_id, 'parent_region', true);
            if ($parent_region == $selected_region) {
                $provinces[] = $prov;
            }
        }
    } else {
        $provinces = get_terms($province_args);
    }
    
    // Build province-region mapping for JavaScript
    $all_provinces_for_js = get_terms(['taxonomy' => 'branch_province', 'hide_empty' => true]);
    $province_region_map = [];
    foreach($all_provinces_for_js as $province) {
        $parent_region = get_term_meta($province->term_id, 'parent_region', true);
        if ($parent_region) {
            $province_region_map[$province->term_id] = $parent_region;
        }
    }
    
    // Query args with pagination
    $query_args = [
        'post_type' => 'branch',
        'posts_per_page' => intval($atts['per_page']),
        'paged' => $current_page
    ];
    
    // Add tax query if region/province selected
    $tax_query = [];
    if ($selected_region && !$selected_province) {
        // If only region selected, get all provinces under this region
        $region_provinces = [];
        foreach($all_provinces_for_js as $prov) {
            $parent_region = get_term_meta($prov->term_id, 'parent_region', true);
            if ($parent_region == $selected_region) {
                $region_provinces[] = $prov->term_id;
            }
        }
        
        if (!empty($region_provinces)) {
            $tax_query[] = [
                'taxonomy' => 'branch_province',
                'field' => 'term_id',
                'terms' => $region_provinces,
                'operator' => 'IN'
            ];
        } else {
            // If no provinces under region, filter by region directly
            $tax_query[] = [
                'taxonomy' => 'branch_region',
                'field' => 'term_id',
                'terms' => $selected_region
            ];
        }
    } elseif ($selected_province) {
        // If province selected, filter by province
        $tax_query[] = [
            'taxonomy' => 'branch_province',
            'field' => 'term_id',
            'terms' => $selected_province
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
      /* ...existing styles... */
      /* Filter Section - Two Row Layout */
      .branch-filters {
          display: flex;
          flex-direction: column;
          gap: 15px;
          margin-bottom: 30px;
      }
      
      .filter-row {
          display: flex;
          gap: 20px;
          flex-wrap: wrap;
          align-items: flex-end;
      }
      
      .filter-row.first-row {
          width: 100%;
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
      }
      @media (max-width: 576px) {
          .branch-grid {
              grid-template-columns: 1fr;
          }
          .filter-group.search-group {
              min-width: 100%;
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

      /* Modern Modal Styles */
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
          background: rgba(0, 0, 0, 0.75);
          display: flex;
          justify-content: center;
          align-items: center;
          z-index: 10000;
          backdrop-filter: blur(5px);
      }
      .modal__container {
          background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
          padding: 0;
          max-width: 1200px;
          width: 90%;
          max-height: 85vh;
          border-radius: 20px;
          overflow: hidden;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
          position: relative;
          animation: modalSlideUp 0.3s ease-out;
      }
      
      @keyframes modalSlideUp {
          from {
              opacity: 0;
              transform: translateY(50px);
          }
          to {
              opacity: 1;
              transform: translateY(0);
          }
      }
      
      .modal__header {
          background: linear-gradient(135deg, #330A48 0%, #4a0e68 100%);
          color: white;
          padding: 25px 30px;
          display: flex;
          justify-content: space-between;
          align-items: center;
          border-bottom: none;
      }
      
      .modal__title {
          margin: 0;
          font-size: 1.8em;
          font-weight: 600;
          color: white;
          text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      }
      
      .modal__close {
          background: rgba(255, 255, 255, 0.2);
          border: none;
          width: 40px;
          height: 40px;
          border-radius: 50%;
          font-size: 24px;
          cursor: pointer;
          color: white;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.3s ease;
          padding: 0;
          line-height: 1;
      }
      
      .modal__close:hover {
          background: rgba(255, 255, 255, 0.3);
          transform: rotate(90deg);
      }
      
      .modal__content {
          padding: 35px;
          overflow-y: auto;
          max-height: calc(85vh - 90px);
      }
      
      .modal__content::-webkit-scrollbar {
          width: 8px;
      }
      
      .modal__content::-webkit-scrollbar-track {
          background: #f1f1f1;
          border-radius: 10px;
      }
      
      .modal__content::-webkit-scrollbar-thumb {
          background: #330A48;
          border-radius: 10px;
      }
      
      .modal__content::-webkit-scrollbar-thumb:hover {
          background: #4a0e68;
      }
      
      .modal__content img {
          width: 100%;
          height: 350px;
          object-fit: cover;
          border-radius: 15px;
          margin-bottom: 25px;
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      }
      
      .modal__content .branch-details {
          display: grid;
          gap: 20px;
      }
      
      .modal__content .detail-item {
          background: white;
          padding: 20px;
          border-radius: 12px;
          border-left: 4px solid #330A48;
          box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
          transition: transform 0.2s ease;
      }
      
      .modal__content .detail-item:hover {
          transform: translateX(5px);
      }
      
      .modal__content .detail-item strong {
          display: block;
          color: #330A48;
          font-size: 0.9em;
          text-transform: uppercase;
          letter-spacing: 1px;
          margin-bottom: 8px;
          font-weight: 700;
      }
      
      .modal__content .detail-item .detail-value {
          color: #333;
          font-size: 1.1em;
          line-height: 1.6;
      }
      
      .modal__content .detail-item.branch-name {
          background: linear-gradient(135deg, #330A48 0%, #4a0e68 100%);
          color: white;
          border: none;
          text-align: center;
      }
      
      .modal__content .detail-item.branch-name strong {
          display: none;
      }
      
      .modal__content .detail-item.branch-name .detail-value {
          color: white;
          font-size: 1.5em;
          font-weight: 600;
      }
      
      @media (max-width: 768px) {
          .modal__container {
              width: 95%;
              max-height: 90vh;
          }
          
          .modal__title {
              font-size: 1.4em;
          }
          
          .modal__content {
              padding: 20px;
          }
          
          .modal__content img {
              height: 250px;
          }
      }
    </style>

    <!-- Filters - Two Row Layout -->
    <div class="branch-filters">
        <!-- First Row: Search -->
        <div class="filter-row first-row">
            <div class="filter-group search-group">
                <label for="branch-search">Search Branches</label>
                <input type="text" id="branch-search" placeholder="Search by name or address...">
            </div>
        </div>
        
        <!-- Second Row: Region and Province -->
        <div class="filter-row">
            <div class="filter-group">
                <label for="region-filter">Region</label>
                <select id="region-filter">
                    <option value="">All Regions</option>
                    <?php foreach($regions as $region): ?>
                        <option value="<?php echo esc_attr($region->term_id); ?>" 
                                <?php selected($selected_region, $region->term_id); ?>>
                            <?php echo esc_html($region->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="province-filter">Province</label>
                <select id="province-filter">
                    <option value="">All Provinces</option>
                    <?php foreach($all_provinces_for_js as $province): 
                        $parent_region = get_term_meta($province->term_id, 'parent_region', true);
                    ?>
                        <option value="<?php echo esc_attr($province->term_id); ?>" 
                                data-region="<?php echo esc_attr($parent_region); ?>"
                                <?php selected($selected_province, $province->term_id); ?>
                                style="<?php echo ($selected_region && $parent_region != $selected_region) ? 'display:none;' : ''; ?>">
                            <?php echo esc_html($province->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
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
                
                $branch_regions = wp_get_post_terms(get_the_ID(), 'branch_region', ['fields' => 'ids']);
                $branch_provinces = wp_get_post_terms(get_the_ID(), 'branch_province', ['fields' => 'ids']);
                $region_ids = !empty($branch_regions) ? implode(',', $branch_regions) : '';
                $province_ids = !empty($branch_provinces) ? implode(',', $branch_provinces) : '';
                
                $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'full') : plugin_dir_url(__DIR__) . 'assets/no-image.png';
            ?>
                <div class="branch-card" 
                     data-branch-id="<?php echo get_the_ID(); ?>"
                     data-title="<?php echo esc_attr(get_the_title()); ?>"
                     data-address="<?php echo esc_attr($address); ?>"
                     data-contact="<?php echo esc_attr($contact); ?>"
                     data-region="<?php echo esc_attr($region_ids); ?>"
                     data-province="<?php echo esc_attr($province_ids); ?>"
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
        if ($selected_region) $base_url = add_query_arg('region', $selected_region, $base_url);
        if ($selected_province) $base_url = add_query_arg('province', $selected_province, $base_url);
        
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

    // Province-Region mapping from PHP
    const provinceRegionMap = <?php echo json_encode($province_region_map); ?>;

    // Branch data for modal and filtering
    const branchData = {};
    <?php 
    $all_branches->rewind_posts();
    while($all_branches->have_posts()): $all_branches->the_post(); 
        $contact = get_post_meta(get_the_ID(), '_branch_contact', true);
        $address = get_post_meta(get_the_ID(), '_branch_address', true);
        $lat = get_post_meta(get_the_ID(), '_branch_lat', true);
        $lng = get_post_meta(get_the_ID(), '_branch_lng', true);
        $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'large') : plugin_dir_url(__DIR__) . 'assets/no-image.png';
        
        $branch_regions = wp_get_post_terms(get_the_ID(), 'branch_region', ['fields' => 'ids']);
        $branch_provinces = wp_get_post_terms(get_the_ID(), 'branch_province', ['fields' => 'ids']);
    ?>
    branchData[<?php echo get_the_ID(); ?>] = {
        title: "<?php echo addslashes(get_the_title()); ?>",
        address: "<?php echo addslashes($address); ?>",
        contact: "<?php echo addslashes($contact); ?>",
        image: "<?php echo $image_url; ?>",
        lat: "<?php echo esc_js($lat); ?>",
        lng: "<?php echo esc_js($lng); ?>",
        regions: [<?php echo !empty($branch_regions) ? implode(',', $branch_regions) : ''; ?>],
        provinces: [<?php echo !empty($branch_provinces) ? implode(',', $branch_provinces) : ''; ?>]
    };
    <?php endwhile; wp_reset_postdata(); ?>

    // Open branch modal with modern layout
    function openBranchModal(branchId) {
        const branch = branchData[branchId];
        if (!branch) return;

        const content = `
            <img src="${branch.image}" alt="${branch.title}">
            <div class="branch-details">
                <div class="detail-item branch-name">
                    <div class="detail-value">${branch.title}</div>
                </div>
                <div class="detail-item">
                    <strong>📍 Address</strong>
                    <div class="detail-value">${branch.address}</div>
                </div>
                <div class="detail-item">
                    <strong>📞 Contact Number</strong>
                    <div class="detail-value">${branch.contact}</div>
                </div>
            </div>
        `;
        
        document.getElementById('branch-modal-content').innerHTML = content;
        document.getElementById('branch-modal-title').textContent = 'Branch Information';
        MicroModal.show('branch-modal');
    }

    // Map toggle functionality
    document.getElementById('map-toggle').addEventListener('click', function() {
        const mapContainer = document.getElementById('map-container');
        const toggleText = document.getElementById('map-toggle-text');
        
        if (mapContainer.style.display === 'none') {
            mapContainer.style.display = 'block';
            toggleText.textContent = 'Hide Map';
            if (!window.branchMapInitialized) {
                initializeMap();
            }
        } else {
            mapContainer.style.display = 'none';
            toggleText.textContent = 'Show Map';
        }
    });

    // Dynamic province filtering based on region
    const searchInput = document.getElementById('branch-search');
    const regionFilter = document.getElementById('region-filter');
    const provinceFilter = document.getElementById('province-filter');
    const branchCards = document.querySelectorAll('.branch-card');
    const allProvinceOptions = Array.from(provinceFilter.querySelectorAll('option'));

    // Update provinces when region changes
    regionFilter.addEventListener('change', function() {
        const selectedRegion = this.value;
        
        // Reset province filter
        provinceFilter.value = '';
        
        // Show/hide provinces based on selected region
        allProvinceOptions.forEach(option => {
            if (option.value === '') {
                option.style.display = 'block'; // Always show "All Provinces"
                return;
            }
            
            const provinceRegion = option.getAttribute('data-region');
            
            if (!selectedRegion || provinceRegion === selectedRegion) {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });
        
        // Reload page with region filter
        updateURLFilters();
    });

    function filterBranches() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedRegion = regionFilter.value;
        const selectedProvince = provinceFilter.value;

        let visibleBranchIds = [];

        branchCards.forEach(card => {
            const branchId = card.dataset.branchId;
            const title = card.dataset.title.toLowerCase();
            const address = card.dataset.address.toLowerCase();
            const cardProvinces = card.dataset.province.split(',').filter(p => p);
            
            const matchesSearch = title.includes(searchTerm) || address.includes(searchTerm);
            
            let matchesRegion = true;
            let matchesProvince = true;
            
            // Check region match
            if (selectedRegion) {
                matchesRegion = false;
                cardProvinces.forEach(provId => {
                    if (provinceRegionMap[provId] == selectedRegion) {
                        matchesRegion = true;
                    }
                });
            }
            
            // Check province match
            if (selectedProvince) {
                matchesProvince = cardProvinces.includes(selectedProvince);
            }

            if (matchesSearch && matchesRegion && matchesProvince) {
                card.style.display = 'block';
                visibleBranchIds.push(branchId);
            } else {
                card.style.display = 'none';
            }
        });

        if (window.branchMapInitialized) {
            updateMapMarkers(visibleBranchIds);
        }
    }

    // Real-time search filtering
    searchInput.addEventListener('input', filterBranches);

    // Province filter with URL reload
    provinceFilter.addEventListener('change', function() {
        updateURLFilters();
    });

    function updateURLFilters() {
        const url = new URL(window.location);
        const region = regionFilter.value;
        const province = provinceFilter.value;
        
        if (region) {
            url.searchParams.set('region', region);
        } else {
            url.searchParams.delete('region');
        }
        
        if (province) {
            url.searchParams.set('province', province);
        } else {
            url.searchParams.delete('province');
        }
        
        url.searchParams.delete('branch_page');
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

        const allBranchIds = Object.keys(branchData);
        updateMapMarkers(allBranchIds);

        window.branchMapInitialized = true;
    }

    function updateMapMarkers(visibleBranchIds) {
        if (!branchMap) return;

        const markers = [];

        Object.keys(allMarkers).forEach(branchId => {
            if (branchMap.hasLayer(allMarkers[branchId])) {
                branchMap.removeLayer(allMarkers[branchId]);
            }
        });

        visibleBranchIds.forEach(branchId => {
            if (allMarkers[branchId]) {
                allMarkers[branchId].addTo(branchMap);
                markers.push(allMarkers[branchId]);
            }
        });

        if (markers.length > 0) {
            const group = L.featureGroup(markers);
            branchMap.fitBounds(group.getBounds(), {
                padding: [50, 50],
                maxZoom: 14
            });
        }
    }

    <?php if (strtolower($atts['show_map']) === 'true'): ?>
    document.addEventListener("DOMContentLoaded", function() {
        initializeMap();
        
        // Run initial filter to match server-side filtering
        filterBranches();
    });
    <?php endif; ?>
    </script>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('branch_map', 'bm_branch_map_shortcode');