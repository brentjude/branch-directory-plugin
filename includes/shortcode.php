<?php
/**
 * Shortcode: [branch_map]
 * Display branches on a map + grid list
 */
function bm_branch_map_shortcode() {
    ob_start();
    $branches = new WP_Query(['post_type' => 'branch', 'posts_per_page' => -1]);
    ?>

    <style>
      .branch-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
          gap: 20px;
          margin-top: 20px;
      }
      .branch-card {
          border: 1px solid #ddd;
          border-radius: 8px;
          padding: 0px;
          background: #330A48;
          text-align: center;
          box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      }
      .branch-image {
          width: 100%;
          height: 200px;
          background-size: cover;
          background-position: center;
          border-radius: 8px;
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
      /* Optional: style tooltips */
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
    </style>

    <!-- Map -->
    <div id="branch-map" style="height:400px; width:100%;"></div>

    <!-- Branches Grid -->
    <div class="branch-grid">
        <?php while($branches->have_posts()): $branches->the_post(); 
            $contact   = get_post_meta(get_the_ID(), '_branch_contact', true);
            $address   = get_post_meta(get_the_ID(), '_branch_address', true);
        ?>
            <div class="branch-card">
                <?php if (has_post_thumbnail()) { 
                    $image_url = get_the_post_thumbnail_url(get_the_ID(), 'medium'); ?>
                    <div class="branch-image" style="background-image: url('<?php echo esc_url($image_url); ?>');"></div>
                <?php } else { ?>
                    <div class="branch-image" style="background-image: url('<?php echo plugin_dir_url(__DIR__) . 'assets/no-image.png'; ?>');"></div>
                <?php } ?>
                <div class="branch-info">
                    <h3><?php the_title(); ?></h3>
                    <p><strong>Address:</strong> <?php echo esc_html($address); ?></p>
                    <p><strong>Contact:</strong> <?php echo esc_html($contact); ?></p>
                </div>
            </div>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>

    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var map = L.map('branch-map').setView([14.5995, 120.9842], 6); // Default PH center
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var markers = [];

        <?php 
        $branches = new WP_Query(['post_type' => 'branch', 'posts_per_page' => -1]);
        while($branches->have_posts()): $branches->the_post(); 
            $lat      = get_post_meta(get_the_ID(), '_branch_lat', true);
            $lng      = get_post_meta(get_the_ID(), '_branch_lng', true);
            $contact  = get_post_meta(get_the_ID(), '_branch_contact', true);
            $address  = get_post_meta(get_the_ID(), '_branch_address', true);
            if ($lat && $lng) {
                $photo = has_post_thumbnail() ? wp_get_attachment_image_url(get_post_thumbnail_id(), 'thumbnail') : plugin_dir_url(__DIR__) . 'assets/no-image.png';
                ?>
                var marker = L.marker([<?php echo esc_js($lat); ?>, <?php echo esc_js($lng); ?>])
                  .addTo(map)
                  .bindPopup(`
                    <div style="text-align:center; max-width:150px;">
                      <img src="<?php echo $photo; ?>" style="max-width:100%; border-radius:6px; margin-bottom:5px;" />
                      <strong><?php echo addslashes(get_the_title()); ?></strong><br>
                      <?php echo addslashes($address); ?><br>
                      Contact: <?php echo addslashes($contact); ?>
                    </div>
                  `)
                  // ✅ Always show branch name beside pin
                  .bindTooltip("<?php echo addslashes(get_the_title()); ?>", { 
                      permanent: true, 
                      direction: "right", 
                      offset: [10, 0] 
                  });

                markers.push(marker);
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
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('branch_map', 'bm_branch_map_shortcode');
