<?php
// Hook to add a metabox to the LearnDash course edit page
add_action('add_meta_boxes', 'ld_course_custom_bg_metabox');

function ld_course_custom_bg_metabox() {
    add_meta_box(
        'ld_course_bg_image',               // ID of the metabox
        'Course Background Image',          // Title of the metabox
        'ld_course_bg_image_metabox_html',  // Callback function to display the metabox
        'sfwd-courses',                     // Post type (LearnDash courses)
        'side',                             // Context (where to display: side, normal)
        'high'                              // Priority (high to make sure it's at the top)
    );
}

// HTML output for the metabox
function ld_course_bg_image_metabox_html($post) {
    // Get the current meta value for the background image
    $bg_image_url = get_post_meta($post->ID, '_ld_course_bg_image', true);

    // Output the field and media button
    ?>
    <div class="ld-course-bg-image-metabox">
        <img id="ld-course-bg-preview" src="<?php echo esc_url($bg_image_url); ?>" style="max-width: 100%; <?php echo empty($bg_image_url) ? 'display:none;' : ''; ?>" />
        <input type="hidden" id="ld-course-bg-image" name="ld_course_bg_image" value="<?php echo esc_url($bg_image_url); ?>">
        <button type="button" class="button" id="ld-course-bg-upload-btn">Select Background Image</button>
        <button type="button" class="button" id="ld-course-bg-remove-btn" style="display:<?php echo empty($bg_image_url) ? 'none' : 'inline-block'; ?>">Remove Image</button>
    </div>

    <script>
    jQuery(document).ready(function($){
        var mediaUploader;

        $('#ld-course-bg-upload-btn').click(function(e) {
            e.preventDefault();
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Select Background Image',
                button: { text: 'Use this image' },
                multiple: false
            });
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#ld-course-bg-image').val(attachment.url);
                $('#ld-course-bg-preview').attr('src', attachment.url).show();
                $('#ld-course-bg-remove-btn').show();
            });
            mediaUploader.open();
        });

        // Remove image functionality
        $('#ld-course-bg-remove-btn').click(function() {
            $('#ld-course-bg-image').val('');
            $('#ld-course-bg-preview').hide();
            $(this).hide();
        });
    });
    </script>
    <?php
}

// Save the background image meta data when the course is saved
add_action('save_post', 'ld_save_course_bg_image');
function ld_save_course_bg_image($post_id) {
    // Check if the field is set, then save the data
    if (isset($_POST['ld_course_bg_image'])) {
        update_post_meta($post_id, '_ld_course_bg_image', esc_url_raw($_POST['ld_course_bg_image']));
    }
}
