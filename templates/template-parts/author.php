<div id="autor-box" class="autor-box">
    <div class="autor-body">
    <div class="autor-header">
        <div class="user-photo-circle">
            <?php 
            $author_id = get_post_field('post_author', get_the_ID());
            $user_photo_url = get_user_meta($author_id, 'profile_picture', true);

            if ($user_photo_url) {
                echo '<img src="' . esc_url($user_photo_url) . '" alt="Profile Photo">';
            } else {
                $first_name = get_the_author_meta('first_name', $author_id);
                echo '<span class="user-initial">' . esc_html(strtoupper(substr($first_name, 0, 1))) . '</span>';
            }
            ?>
        </div>
        <div class="autor-info">
            <div class="autor-name">
                <?php 
                $first_name = get_the_author_meta('first_name', $author_id);
                $last_name = get_the_author_meta('last_name', $author_id);
                echo esc_html($first_name . ' ' . $last_name);
                ?>
            </div>
            <div class="autor-title">
                <?php 
                $titulo_personal = get_user_meta($author_id, 'titulo_personal', true);
                echo esc_html($titulo_personal);
                ?>
            </div>
        </div>
    </div>
        <div class="autor-column autor-bio">
            <p style="font-size: 24px; font-weight: 300;"><strong>Sobre el autor</strong></p>
            <p><?php echo esc_html(get_the_author_meta('description', $author_id)); ?></p>
        </div>
    </div>
</div>