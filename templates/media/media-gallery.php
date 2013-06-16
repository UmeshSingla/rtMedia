<div class="rt-media-container rt-media-list-container">

    <?php if (have_rt_media()) { ?>

        <h2><?php echo __('Media Gallery','rt-media'); ?></h2>

        <ul class="rt-media-list">

            <?php while (have_rt_media()) : rt_media(); ?>

                <?php include ('media-gallery-item.php'); ?>

            <?php endwhile; ?>

        </ul>


        <!--  these links will be handled by backbone later
                        -- get request parameters will be removed  -->
        <?php if(rt_media_offset() != 0) { ?>
            <a href="?rt_media_page=<?php echo rt_media_page()-1; ?>">Prev</a>
        <?php } ?>
        <?php if(rt_media_offset()+ rt_media_per_page_media() < rt_media_count()) { ?>
            <a href="?rt_media_page=<?php echo rt_media_page()+1; ?>">Next</a>
        <?php } ?>

    <?php } ?>

</div>

<script id="rt-media-gallery-item-template" type="text/template">
    <div class="rt-media-item-thumbnail">
        <a href ="media/<%= id %>">
            <img src="<%= guid %>">
        </a>
    </div>

    <div class="rt-media-item-title">
        <h4 title="<%= media_title %>">
            <a href="media/<%= id %>">
                <%= media_title %>
            </a>
        </h4>
    </div>

    <div class="rt-media-item-actions">
        <%= rt_media_actions %>
    </div>
</script>