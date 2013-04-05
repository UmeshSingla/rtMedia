<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BPMediaBPAlbumImporter
 *
 * @author saurabh
 */
class BPMediaAlbumimporter extends BPMediaImporter {

    function __construct() {
        global $wpdb;
        parent::__construct();
        $table = "{$wpdb->base_prefix}bp_album";
        if (BPMediaImporter::table_exists($table) && BPMediaAlbumimporter::_active('bp-album/loader.php') != -1 && !$this->column_exists('import_status')) {
            $this->update_table();
        }
    }

    function update_table() {
        if ($this->column_exists('import_status'))
            return;
        global $wpdb;
        return $wpdb->query(
                        "ALTER TABLE {$wpdb->base_prefix}bp_album ADD COLUMN
					import_status TINYINT (1) NOT NULL DEFAULT 0"
        );
    }

    function column_exists($column) {
        global $wpdb;
        return $wpdb->query(
                        "SHOW COLUMNS FROM {$wpdb->base_prefix}bp_album LIKE '$column'"
        );
    }

    function ui() {
        $this->progress = new rtProgress();
        $total = BPMediaAlbumimporter::get_total_count();
        $remaining_comments = $this->get_remaining_comments();
        $finished = BPMediaAlbumimporter::get_completed_media($total);
        $finished_users = BPMediaAlbumimporter::get_completed_users();
        $finished_comments = $this->get_finished_comments();
        $total_comments = (int) $finished_comments + (int) $remaining_comments;

        //(isset($total) && isset($finished) && is_array($total) && is_array($finished)){
        echo '<div id="bpmedia-bpalbumimporter">';
        if ($finished[0]->media != $total[0]->media) {
            if (!$total[0]->media) {
                echo '<p><strong>' . __('You have nothing to import') . '</strong></p>';
            } elseif (BPMediaAlbumimporter::_active('bp-album/loader.php') != 1) {
                echo '<div id="setting-error-bp-album-importer" class="error settings-error below-h2">
<p><strong>' . __('Warning!', BP_MEDIA_TXT_DOMAIN) . '</strong> ' . sprintf(__('This import process is irreversible. Although everything is tested, please take a <a target="_blank" href="http://codex.wordpress.org/WordPress_Backups">backup of your database and files</a>, before proceeding. If you don\'t know your way around databases and files, consider <a target="_blank" href="%s">hiring us</a>, or another professional.', BP_MEDIA_TXT_DOMAIN), 'http://rtcamp.com/contact/?purpose=buddypress&utm_source=dashboard&utm_medium=plugin&utm_campaign=buddypress-media') . '</p></div>';
                echo '<div class="bp-album-import-accept"><p><strong><label for="bp-album-import-accept"><input type="checkbox" value="accept" name="bp-album-import-accept" id="bp-album-import-accept" /> ' . __('I have taken a backup of the database and files of this site.', BP_MEDIA_TXT_DOMAIN) . '</label></strong></p></div>';
                echo '<button id="bpmedia-bpalbumimport" class="button button-primary">';
                _e('Start Import', BP_MEDIA_TXT_DOMAIN);
                echo '</button>';
                echo '<div class="bp-album-importer-wizard">';
                echo '<div class="bp-album-users">';
                echo '<strong>';
                echo __('Users', BP_MEDIA_TXT_DOMAIN) . ': <span class="finished">' . $finished_users[0]->users . '</span> / <span class="total">' . $total[0]->users . '</span>';
                echo '</strong>';
                if ($total[0]->users != 0) {
                    $users_progress = $this->progress->progress($finished_users[0]->users, $total[0]->users);
                    $this->progress->progress_ui($users_progress);
                }
                echo '</div>';
                echo '<br />';
                echo '<div class="bp-album-media">';
                echo '<strong>';
                echo __('Media', BP_MEDIA_TXT_DOMAIN) . ': <span class="finished">' . $finished[0]->media . '</span> / <span class="total">' . $total[0]->media . '</span>';
                echo '</strong>';
                $progress = 100;
                if ($total[0]->media != 0) {
                    $todo = $total[0]->media - $finished[0]->media;
                    $steps = ceil($todo / 5);
                    $laststep = $todo % 5;
                    $progress = $this->progress->progress($finished[0]->media, $total[0]->media);
                    echo '<input type="hidden" value="' . $finished[0]->media . '" name="finished"/>';
                    echo '<input type="hidden" value="' . $total[0]->media . '" name="total"/>';
                    echo '<input type="hidden" value="' . $todo . '" name="todo"/>';
                    echo '<input type="hidden" value="' . $steps . '" name="steps"/>';
                    echo '<input type="hidden" value="' . $laststep . '" name="laststep"/>';
                    $this->progress->progress_ui($progress);
                }
                echo '</div>';
                echo "<br>";
                if ($total_comments != 0) {

                    echo '<div class="bp-album-comments">';
                    echo '<strong>';
                    echo __('Comments', BP_MEDIA_TXT_DOMAIN) . ': <span class="finished">' . $finished_comments . '</span> / <span class="total">' . $total_comments . '</span>';
                    echo '</strong>';
                    $comments_progress = $this->progress->progress($finished_comments, $total_comments);
                    $this->progress->progress_ui($comments_progress);
                    echo '</div>';
                    echo '<br />';
                } else {
                    echo '<p><strong>' . __('Comments: 0/0 (No comments to import)', BP_MEDIA_TXT_DOMAIN) . '</strong></p>';
                }
                echo '</div>';
            } else {
                $deactivate_link = wp_nonce_url(admin_url('plugins.php?action=deactivate&amp;plugin=' . urlencode($this->path)), 'deactivate-plugin_' . $this->path);
                echo '<p>' . __('BP-Album is active on your site and will cause problems with the import.', BP_MEDIA_TXT_DOMAIN) . '</p>';
                echo '<p><a class="button button-primary deactivate-bp-album" href="' . $deactivate_link . '">' . __('Click here to deactivate BP-Album and continue importing', BP_MEDIA_TXT_DOMAIN) . '</a></p>';
            }
        } else {
            echo '<div class="bp-album-import-accept i-accept">';
            echo '<p class="info">';
            $message = sprintf(__('I just imported bp-album to @buddypressmedia http://goo.gl/8Upmv on %s', BP_MEDIA_TXT_DOMAIN), home_url());
            echo '<strong>'.__('Congratulations!',BP_MEDIA_TXT_DOMAIN).'</strong> '. __('All media from BP Album has been imported.',BP_MEDIA_TXT_DOMAIN); 
            echo ' <a href="http://twitter.com/home/?status='.$message.'" class="button button-import-tweet" target= "_blank">' . __('Tweet this', BP_MEDIA_TXT_DOMAIN) . '</a>';
            echo '</p>';
            echo '</div>';
            echo '<p>'.__('However, a lot of unnecessary files and a database table are still eating up your resources. If everything seems fine, you can clean this data up.', BP_MEDIA_TXT_DOMAIN);
            echo '<br />';
            echo '<br />';
            echo '<button id="bpmedia-bpalbumimport-cleanup" class="button btn-warning">';
            _e('Clean up Now', BP_MEDIA_TXT_DOMAIN);
            echo '</button>';
            echo ' <a href="'.add_query_arg(
                            array('page' => 'bp-media-settings'), (is_multisite() ? network_admin_url('admin.php') : admin_url('admin.php'))
                    ).'" id="bpmedia-bpalbumimport-cleanup-later" class="button">';
            _e('Clean up Later', BP_MEDIA_TXT_DOMAIN);
            echo '</a>';
        }
        echo '</div>';
    }

    function create_album($author_id, $album_name = 'Imported Media') {
        global $bp_media, $wpdb;

        if (array_key_exists('bp_album_import_name', $bp_media->options)) {
            if ($bp_media->options['bp_album_import_name'] != '') {
                $album_name = $bp_media->options['bp_album_import_name'];
            }
        }

        $query = "SELECT ID from $wpdb->posts WHERE post_type='bp_media_album' AND post_status = 'publish' AND post_author = $author_id AND post_title LIKE '{$album_name}'";
        $result = $wpdb->get_results($query);
        if (count($result) < 1) {
            $album = new BPMediaAlbum();
            $album->add_album($album_name, $author_id);
            $album_id = $album->get_id();
        } else {
            $album_id = $result[0]->ID;
        }
        $wpdb->update($wpdb->base_prefix . 'bp_activity', array('secondary_item_id' => -999), array('id' => get_post_meta($album_id, 'bp_media_child_activity', true)));

        return $album_id;
    }

    static function get_total_count() {
        global $wpdb;
        $table = $wpdb->base_prefix . 'bp_album';
        if (BPMediaAlbumimporter::table_exists($table) && BPMediaAlbumimporter::_active('bp-album/loader.php') != -1) {
            return $wpdb->get_results("SELECT COUNT(DISTINCT owner_id) as users, COUNT(id) as media FROM $table");
        }
        return 0;
    }

    function get_remaining_comments() {
        global $wpdb;
        $bp_album_table = $wpdb->base_prefix . 'bp_album';
        $activity_table = $wpdb->base_prefix . 'bp_activity';
        if ($this->table_exists($bp_album_table) && BPMediaAlbumimporter::_active('bp-album/loader.php') != -1) {
            return $wpdb->get_var("SELECT SUM( b.count ) AS total
                                        FROM (
                                            SELECT (
                                                SELECT COUNT( a.id ) 
                                                FROM $activity_table a
                                                WHERE a.item_id = activity.id
                                                AND a.component =  'activity'
                                                AND a.type =  'activity_comment'
                                            ) AS count
                                            FROM $activity_table AS activity
                                            INNER JOIN $bp_album_table AS album ON ( album.id = activity.item_id ) 
                                            WHERE activity.component =  'album'
                                            AND activity.type =  'bp_album_picture'
                                            AND album.import_status =0
                                        )b");
        }
        return 0;
    }

    function get_finished_comments() {
        global $wpdb;
        $bp_album_table = $wpdb->base_prefix . 'bp_album';
        $activity_table = $wpdb->base_prefix . 'bp_activity';
        if ($this->table_exists($bp_album_table) && BPMediaAlbumimporter::_active('bp-album/loader.php') != -1) {
            return $wpdb->get_var("SELECT COUNT( activity.id ) AS count
                                        FROM $activity_table AS activity
                                        INNER JOIN $bp_album_table AS album ON ( activity.item_id = album.import_status ) 
                                        WHERE activity.component =  'activity'
                                        AND activity.type =  'activity_comment'");
        }
        return 0;
    }

    static function get_completed_users() {
        global $wpdb;
        $table = $wpdb->base_prefix . 'bp_album';
        if (BPMediaAlbumimporter::table_exists($table) && BPMediaAlbumimporter::_active('bp-album/loader.php') != -1) {
            return $wpdb->get_results("SELECT COUNT( DISTINCT owner_id ) AS users
                                            FROM $table
                                            WHERE owner_id NOT 
                                            IN (
                                                SELECT a.owner_id
                                                FROM $table a
                                                WHERE a.import_status =0
                                            )
                                        ");
        }
        return 0;
    }

    static function get_completed_media() {
        global $wpdb;
        $table = $wpdb->base_prefix . 'bp_album';
        if (BPMediaAlbumimporter::table_exists($table) && BPMediaAlbumimporter::_active('bp-album/loader.php') != -1) {
            return $wpdb->get_results("SELECT COUNT(id) as media FROM $table WHERE import_status!=0");
        }
        return 0;
    }

    static function batch_import($count = 5) {
        global $wpdb;
        $table = $wpdb->base_prefix . 'bp_album';
        $bp_album_data = $wpdb->get_results("SELECT * FROM $table WHERE import_status = 0 ORDER BY owner_id LIMIT $count");
        return $bp_album_data;
    }

    static function bpmedia_ajax_import_callback() {

        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $count = isset($_GET['count']) ? $_GET['count'] : 5;
        $bp_album_data = BPMediaAlbumimporter::batch_import($count);
        global $wpdb;
        $table = $wpdb->base_prefix . 'bp_album';
        $comments = 0;
        foreach ($bp_album_data as &$bp_album_item) {

            if (get_site_option('bp_media_bp_album_importer_base_path') == '') {
                $base_path = pathinfo($bp_album_item->pic_org_path);
                update_site_option('bp_media_bp_album_importer_base_path', $base_path['dirname']);
            }
            $bpm_host_wp = new BPMediaHostWordpress();
            $bpm_host_wp->check_and_create_album(0, 0, $bp_album_item->owner_id);
            $album_id = BPMediaAlbumimporter::create_album($bp_album_item->owner_id, 'Imported Media');
            $imported_media_id = BPMediaImporter::add_media(
                            $album_id, $bp_album_item->title, $bp_album_item->description, $bp_album_item->pic_org_path, $bp_album_item->privacy, $bp_album_item->owner_id, 'Imported Media'
            );
            $comments += (int) BPMediaAlbumimporter::update_recorded_time_and_comments($imported_media_id, $bp_album_item->id, "{$wpdb->base_prefix}bp_album");
            $wpdb->update($table, array('import_status' => $imported_media_id), array('id' => $bp_album_item->id), array('%d'), array('%d'));
        }

        $finished_users = BPMediaAlbumimporter::get_completed_users();

        echo json_encode(array('page' => $page, 'users' => $finished_users[0]->users, 'comments' => $comments));
        die();
    }

    static function cleanup_after_install() {
        global $wpdb;
        $table = $wpdb->base_prefix . 'bp_album';
        $dir = get_site_option('bp_media_bp_album_importer_base_path');
        BPMediaImporter::cleanup($table, $dir);
        die();
    }

    static function update_recorded_time_and_comments($media, $bp_album_id, $table) {
        global $wpdb;
        if (function_exists('bp_activity_add')) {
            if (!is_object($media)) {
                try {
                    $media = new BPMediaHostWordpress($media);
                } catch (exception $e) {
                    return false;
                }
            }
            $activity_id = get_post_meta($media->get_id(), 'bp_media_child_activity', true);
            if ($activity_id) {
                $date_uploaded = $wpdb->get_var("SELECT date_uploaded from $table WHERE id = $bp_album_id");
                $old_activity_id = $wpdb->get_var("SELECT id from {$wpdb->base_prefix}bp_activity WHERE component = 'album' AND type = 'bp_album_picture' AND item_id = $bp_album_id");
                $comments = $wpdb->get_results("SELECT id,secondary_item_id from {$wpdb->base_prefix}bp_activity WHERE component = 'activity' AND type = 'activity_comment' AND item_id = $old_activity_id");
                foreach ($comments as $comment) {
                    $update = array('item_id' => $activity_id);
                    if ($comment->secondary_item_id == $old_activity_id) {
                        $update['secondary_item_id'] = $activity_id;
                    }
                    $wpdb->update($wpdb->base_prefix . 'bp_activity', $update, array('id' => $comment->id));
                    BP_Activity_Activity::rebuild_activity_comment_tree($activity_id);
                }
                $wpdb->update($wpdb->base_prefix . 'bp_activity', array('date_recorded' => $date_uploaded), array('id' => $activity_id));
                return count($comments);
            }

            return 0;
        }
    }

    static function bp_album_deactivate() {
        deactivate_plugins('bp-album/loader.php');
        die(true);
    }

}

?>