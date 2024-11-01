<?php
/**
 * Created by PhpStorm.
 * User: Enterprise
 * Date: 16/05/2016
 * Time: 12:39
 */
// require_once('../../../../wp-admin/includes/taxonomy.php');
if (!class_exists('wrConnector')) {
    require_once(WHITERABBIT_PATH . "connector/connector.php");
}

class whiterabbit_client
{

    public function init()
    {
        add_filter('init', array(new whiterabbit_client(), 'rewrite_rules'));
    }

    public function flush_rules()
    {
        $this->rewrite_rules();
        flush_rewrite_rules();
        // echo "Flush rules complete!!!<BR/><BR/>";
    }

    public function rewrite_rules()
    {
        // add_rewrite_rule( "wr\/api\/(.+?)\/(\?(.+?))$", 'index.php?wr_action=$matches[1]', 'top');
        add_rewrite_rule('^api/([^/]*)/([^/]*)/?', 'index.php?wr_action=$matches[1]', 'top');
        // echo "Rewrite rules complete!!!<BR/><BR/>";
        add_rewrite_tag('%wr_action%', '([^&]+)');
        add_rewrite_tag('%username%', '([^&]+)');
        add_rewrite_tag('%password%', '([^&]+)');
        add_rewrite_tag('%suitetoken%', '([^&]+)');
        add_rewrite_tag('%token%', '([^&]+)');
        add_rewrite_tag('%type%', '([^&]+)');
        add_rewrite_tag('%content_id%', '([^&]+)');
        add_rewrite_tag('%content%', '([^&]+)');
    }

    public function whiterabbit_api_request($wp)
    {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $wr_connect = new wrConnector();
        global $wpdb;

        $valid_actions = array('connect', 'publish');

        if (!isset($wp->query_vars['wr_action'])) {
            return;
        }
        $action = $wp->query_vars['wr_action'];

        if (!empty($action) && in_array($action, $valid_actions)) {
            // echo "Azione permessa!!!<br><br> ($action | " . json_encode($wp) . ")";

            switch ($action) {
                case "connect":
                    // echo "CONNECT<br>\n";

                    //$username = $wp->query_vars['username'];
                    //$password = $wp->query_vars['password'];
                    $suitetoken = !empty($wp->query_vars['suitetoken'])?$wp->query_vars['suitetoken']: null;
                    $suitetoken_wpdb = get_option("wr_general_site_token");

                    $result = false;
                    $token = "";
                    $error = false;
                    $error_text = "";
                    $categories = array();
                    $authors = array();

                    if ($suitetoken == "") {
                        $error = true;
                        $error_text = "unspecified token!";
                    } else {
                        if ($suitetoken == $suitetoken_wpdb) {
                            $token = "ajiG62GSol2iSHs8)A0a9SJhsHUOWo2i2jI2h8s73bSIwu3IUHSi"; // $p->generateToken($length);
                            $result = true;
                            $categories = get_categories(array('hide_empty' => false));
                            $authors = get_users(['role__in' => ['administrator', 'editor', 'author'], 'orderby' => ['name']]);

                        } else {
                            $error = true;
                            $error_text = "username or password are wrong!";
                        }
                    }

                    $response = array(
                        "result" => $result,
                        "categories" => $categories,
                        "authors" => $authors,
                        "token" => $token,
                        "error" => $error,
                        "error_message" => $error_text
                    );

                    break;
                case "publish":
                    // $request = $this->getRequest();
                    $warning_text = Null;
                    $token = $wp->query_vars['token'];
                    $type = $wp->query_vars['type'];
                    $content_id = $wp->query_vars['content_id'];
                    $content_post = $_POST['content'];
                    $content = array();

                    foreach ($content_post['content'] as $key => $value) {
                        if ($key == "body") {
                            $content['content'][$key] = $value;
                            continue;
                        }
                        $content['content'][$key] = sanitize_text_field($value);
                    }

                    $response = array(
                        "result" => true,
                        "error" => false,
                        "error_text" => "",
                        "content_url" => ""
                    );

                    //  $status = 'publish';
                    //  $status = 'draft';
                    //  $status = 'trash';
                    //  $status = 'future';

                    $content_body = $content['content']['body'];

                    switch ($type) {

                        case "post":

                            $post_date = $content['content']['post_date'];
                            $post_status = $content['content']['post_status'];
                            $post_date_gmt = $content['content']['post_date_gmt'];
                            $author_id = $content['content']['author_id'];

                            if ($author_id == Null) {
                                $author_id = get_option("wr_post_default_author_id");
                            }

                            if ($post_status == Null) {
                                $post_status = 'publish';
                            }


                            if ($post_status != "future") {
                                $post_date_gmt = Null;
                            }

                            if ($post_status == "future") {
                                $post_date = Null;
                            }

                            $post_id = 0;
                            $table_name = $wpdb->prefix . '_whiterabbit_posts';
                            $args = array(
                                'meta_key' => 'wr_post_id',
                                'meta_value' => $content_id,
                                'post_type' => 'post',
                                'post_status' => 'any',
                                'posts_per_page' => -1
                            );
                            $posts = get_posts($args);
                            if ($posts) {
                                foreach ($posts as $current_post) {
                                    $post_id = $current_post->ID;
                                }
                            }

                            // $postId = $wpdb->get_var("SELECT whiterabbit_post_id FROM $table_name WHERE post_code = '$content_id';");
                            // $post = Mage::getModel("wr_connector/posts")->loadByPostId($content_id);
                            if (!$post_id) {

                                $category_id = $content['content']['category_id'];


                                if ($category_id == Null) {
                                    if (!($category_id = get_option("wr_post_default_category_id"))) {
                                        if (!($category_id = get_cat_ID('whiterabbit'))) {
                                            $args = array('description' => 'Whiterabbit posts');
                                            $category_id = wp_insert_term("whiterabbit", "category", $args);
                                            //$category_id = wp_create_category('whiterabbit');
                                        }
                                    }
                                }

                                //$author_id = get_option("wr_post_default_author_id");

                                $my_post = array(
                                    'post_title' => wp_strip_all_tags($content['content']['title']),
                                    'post_content' => $content_body,
                                    'post_status' => $post_status,
                                    'post_date_gmt' => $post_date_gmt,
                                    'post_date' => $post_date,
                                    'post_category' => array($category_id),
                                    'post_author' => $author_id,
                                    'guid' => 'wr_post' . $content_id
                                );

                                try {
                                    //$insertid = $wpdb->insert_id;
                                    $post_id = wp_insert_post($my_post);

                                    //aggiorno eventualmete le immagini

                                    $newBody = uploadMedia($content_body, $post_id);

                                    $response["debug"] = $newBody;

                                    if ($newBody['result'] == true) {
                                        $content_body = $newBody['content_body'];

                                        $my_post = array(
                                            'ID' => $post_id,
                                            'post_content' => $content_body,
                                        );
                                        wp_update_post($my_post);
                                    }


                                    add_post_meta($post_id, 'wr_post_id', $content_id);

                                    //simple-seo-pack Plug in

                                    if (!is_plugin_active('simple-seo-pack/simple-seo-pack.php')) {
                                        $warning_text = "Plug in simple-seo-pack not Installed";
                                    } else {
                                        add_post_meta($post_id, '_sseo_meta_description', $content['content']['meta_description']);
                                        add_post_meta($post_id, '_sseo_meta_keywords', $content['content']['meta_keywords']);
                                    }

                                    $response["content_url"] = get_site_url() . "?p=" . $post_id;
                                    $response["content_id"] = $post_id;
                                    $response["post_status"] = $post_status;

                                    if ($post_status == "publish") {
                                        $response["post_date"] = $post_date;
                                    }

                                    if ($post_status == "draft") {
                                        $response["post_date"] = Null;
                                    }

                                    if ($post_status == "future") {
                                        $response["post_date"] = $post_date_gmt;
                                    }


                                    $response["warning_text"] = $warning_text;
                                } catch (Exception $e) {
                                    $wr_connect->wrErrorLog("Save post error: " . $e->getMessage());
                                    $response["error"] = true;
                                    $response["error_text"] = $e->getMessage();
                                }

                            } else {
                                $category_id = $content['content']['category_id'];
                                if ($category_id == Null) {
                                    if (!($category_id = get_option("wr_post_default_category_id"))) {
                                        if (!($category_id = get_cat_ID('whiterabbit'))) {
                                            $args = array('description' => 'Whiterabbit posts');
                                            $category_id = wp_insert_term("whiterabbit", "category", $args);
                                            //$category_id = wp_create_category('whiterabbit');
                                        }
                                    }
                                }

                                //aggiorno immagini

                                $newBody = uploadMedia($content_body, $post_id);


                                //$response["debug"] = $author_id;

                                $content_body = $newBody['content_body'];

                                $my_post = array(
                                    'ID' => $post_id,
                                    'post_title' => wp_strip_all_tags($content['content']['title']),
                                    'post_content' => $content_body,
                                    'post_category' => array($category_id),
                                    'post_status' => $post_status,
                                    'post_date_gmt' => $post_date_gmt,
                                    'post_date' => $post_date,
                                    'post_author' => $author_id,
                                );

                                try {

                                    wp_update_post($my_post);

                                    //simple-seo-pack Plug in
                                    if (!is_plugin_active('simple-seo-pack/simple-seo-pack.php')) {
                                        $warning_text = "Plug in simple-seo-pack not Installed";
                                    } else {
                                        update_post_meta($post_id, '_sseo_meta_description', $content['content']['meta_description']);
                                        update_post_meta($post_id, '_sseo_meta_keywords', $content['content']['meta_keywords']);
                                    }

                                    $response["content_url"] = get_site_url() . "?p=" . $post_id;
                                    $response["content_id"] = $post_id;
                                    $response["warning_text"] = $warning_text;
                                    $response["post_status"] = $post_status;

                                    if ($post_status == "publish") {
                                        $response["post_date"] = $post_date;
                                    }

                                    if ($post_status == "draft") {
                                        $response["post_date"] = Null;
                                    }

                                    if ($post_status == "future") {
                                        $response["post_date"] = $post_date_gmt;
                                    }

                                } catch (Exception $e) {
                                    $wr_connect->wrErrorLog("Update post error: " . $e->getMessage());
                                    $response["error"] = true;
                                    $response["error_text"] = $e->getMessage();
                                }
                            }

                            break;
                        case "landing":
                            $dir = "/wr-landing/";
                            $upload_dir = wp_upload_dir();
                            wp_mkdir_p($upload_dir['path'] . $dir);

                            $nameFile = wp_strip_all_tags($content['content']['title']) . ".html";
                            $nameFile = str_replace(" ", "", $nameFile);
                            $myfile = fopen($upload_dir['path'] . $dir . $nameFile, "w");
                            $contentWeb = wp_remote_get($content['content']['link'],
                                [
                                    'timeout' => 120,
                                    'httpversion' => '1.1',
                                ]
                            );
                            //fwrite($myfile, headLanding());
                            fwrite($myfile, $contentWeb['body']);
                            fwrite($myfile, footerLanding());
                            fclose($myfile);
                            $urlFile = $upload_dir['url'] . $dir . $nameFile;
                            $response["content_url"] = $urlFile;
                            $response["content_id"] = $urlFile;
                            //	$response["debug"] = $upload_dir;
                            //	$response["post_date"] = $post_date;

                            break;
                        default:
                            $response["error"] = true;
                            $response["error_text"] = "type non supported!";
                            $wr_connect->wrErrorLog("Type non gestito!");
                            break;
                    }
                    break;
                default:
                    $error = true;
                    $error_text = "command not valid!";

                    $response = array(
                        "result" => true,
                        "token" => "",
                        "error" => $error,
                        "error_message" => $error_text
                    );
                    break;
            }

            echo json_encode($response);
            die();
        }
    }
}

function array_value_recursive($key, array $arr)
{
    $val = array();
    array_walk_recursive($arr, function ($v, $k) use ($key, &$val) {
        if ($k == $key) array_push($val, $v);
    });
    return count($val) > 1 ? $val : array_pop($val);
}

function whiterabbit_get_sitemap($name, $postsForSitemap)
{
    $sitemap = Null;
    if (str_replace('-', '', get_option('gmt_offset')) < 10) {
        $tempo = '-0' . str_replace('-', '', get_option('gmt_offset'));
    } else {
        $tempo = get_option('gmt_offset');
    }
    if (strlen($tempo) == 3) {
        $tempo = $tempo . ':00';
    }
    $sitemap .= '<?xml version="1.0" encoding="UTF-8"?>';
    $sitemap .= "\n" . '<urlset
          xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
          http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
    $sitemap .= "<!-- created Sitemap with WhiteRabbit Plugin -->";
    $sitemap .= "\t" . '<url>' . "\n" .
        "\t\t" . '<loc>' . esc_url(home_url('/')) . '</loc>' .
        "\n\t\t" . '<lastmod>' . date("Y-m-d\TH:i:s", current_time('timestamp', 0)) . $tempo . '</lastmod>' .
        "\n\t\t" . '<changefreq>monthly</changefreq>' .
        "\n\t\t" . '<priority>1.0</priority>' .
        "\n\t" . '</url>' . "\n";
    foreach ($postsForSitemap as $post) {
        setup_postdata($post);
        $postdate = explode(" ", $post->post_modified);
        $sitemap .= "\t" . '<url>' . "\n" .
            "\t\t" . '<loc>' . get_permalink($post->ID) . '</loc>' .
            "\n\t\t" . '<lastmod>' . $postdate[0] . 'T' . $postdate[1] . $tempo . '</lastmod>' .
            "\n\t\t" . '<changefreq>Weekly</changefreq>' .
            "\n\t\t" . '<priority>0.5</priority>' .
            "\n\t" . '</url>' . "\n";
    }
    $sitemap .= '</urlset>';
    $fp = fopen(ABSPATH . $name, 'w');
    fwrite($fp, $sitemap);
    fclose($fp);

}


function headLanding()
{
    $str = "<?php ?>";
    return $str;
}

function footerLanding()
{
    $script = "<script>$(function() {";
    $script .= "if(location.search == '?captcha'){
      $('form').prepend('<div class=\'alert alert-danger\'><strong>". esc_js(__('Error! ','white-rabbit-suite')) ."</strong>". esc_js(__('Captcha Invalid!','white-rabbit-suite')) ."</div>');
    }else{
        if(location.search == '?success'){
            $('form').prepend('<div class=\'alert alert-success\'><strong>" . esc_js(__('Success! ','white-rabbit-suite')) . "</strong>" . esc_js(__('Contact Form Successfully Submitted!','white-rabbit-suite')) . "</div>');
        }
    }";
    $script .= "$('form').submit(function(e) {                    
                      var response = grecaptcha.getResponse()
                      if(!response){                         
                         $('.msg').remove();
                            $(this).append('<div class=\'msg alert alert-danger\'>" . esc_js(__('Captcha Invalid!','white-rabbit-suite')) . "</div>')
                            e.preventDefault();
                        }
                    });";
    $script .= "});</script>";

    return $script;
}

function uploadMedia($content_body, $parent_post_id)
{
    $result = $content_body;

    /*re = '/<img\s[^>]*?alt\s*=\s*[\'\"]([^\'\"]*?)[\'\"]\s*src\s*=\s*[\'\"]([^\'\"]*?)[\'\"][^>]*title\s*=\s*[\'\"]([^\'\"]*?)[\'\"][^>]*?>/mi';
    */

    //espressione regolare che ritorna array con le chiavi e i valori relativi per src alt e title

    $re = '/<img\s[^>]*?(alt\s*=\s*[\'\"](?P<alt>[^\'\"]*?)[\'\"]\s*|src\s*=\s*[\'\"](?P<src>[^\'\"]*?)[\'\"]\s*|title\s*=\s*[\'\"](?P<title>[^\'\"]*?)[\'\"]\s*){1,3}[^>\/]*?[\/]?>/i';

    preg_match_all($re, stripslashes($content_body), $matches, PREG_SET_ORDER, 0);

    if (count($matches) == 0) {
        $contArray['result'] = false;
        $contArray['content_body'] = $result;
    }

    foreach ($matches as $img) {
        /*re = '/<img\s[^>]*?alt\s*=\s*[\'\"]([^\'\"]*?)[\'\"]\s*src\s*=\s*[\'\"]([^\'\"]*?)[\'\"][^>]*title\s*=\s*[\'\"]([^\'\"]*?)[\'\"][^>]*?>/mi';
        $fileDesc = $img[1];
        $file = $img[2] ;
        $fileTitle = $img[3];     */

        $file = $img['src'];
        $fileTitle = '';
        $fileDesc = '';

        if (substr($file, 0, 2) == "//") {
            $file = 'https:' . $file;
        }

        if (!empty($img['title'])) {
            $fileTitle = $img['title'];
        }
        if (!empty($img['alt'])) {
            $fileDesc = $img['alt'];
        }

        //$file = 'https://enterprise-dev.whiterabbit.online/s3_file_manager/Files/media/12313890_1644530662463379_3052888995909571644_n.png?ref_id=#ref-id#';


        $file = str_replace('?ref_id=#ref-id#', '', $file);

        $filename = basename($file);

        //$media = get_attached_media( 'image', $parent_post_id );

        $upload_file = wp_upload_bits($filename, null, file_get_contents($file));

        // Le immagini vengono caricate sempre

        if (!$upload_file['error']) {
            $wp_filetype = wp_check_filetype($filename, null);
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_parent' => $parent_post_id,
                'post_title' => $fileTitle,
                'post_content' => '',
                'post_excerpt' => $fileDesc,
                'post_status' => 'inherit'
            );

            if (!function_exists('wp_generate_attachment_metadata')) {
                include_once(ABSPATH . 'wp-admin/includes/image.php');
            }


            $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $parent_post_id);
            if (!is_wp_error($attachment_id)) {


                $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                wp_update_attachment_metadata($attachment_id, $attachment_data);
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $fileTitle);
            }

            // $attachment array con tutte le immgini miniature create

            $width = " width=\"" . $attachment_data['width'] . "\"";

            $height = " height=\"" . $attachment_data['height'] . "\"";

            $dimension = $width . $height;

            /*
             devo cambiare le url delle immagini e passare dalle immagini della suite

             <img alt="Alternative Text" src="//enterprise-dev.whiterabbit.online/s3_file_manager/Files/media/img_fjords.jpg?ref_id=#ref-id#" title="  Advisory Title" />
             a quelle di wp
             [caption id="attachment_344" width="600" ]<img alt="Alternative Text" src="http://wptest.demo-whiterabbit.it/wp-content/uploads/2017/05/img_fjords.jpg" title="  Advisory Title"  width="600" height="400" />Alternative Text[/caption]

			*/

            $preCaption = "[caption id=\"attachment_" . $attachment_id . "\"" . $width . " ]";
            $postCaption = $fileDesc . "[/caption]";

            $upload_dir = wp_upload_dir();
            $newImg = $upload_dir['url'] . "/" . $filename;

            $result = str_replace($img[0], $preCaption . $img[0] . $postCaption, stripslashes($result));

            $result = str_replace($img['src'], $newImg, $result);

            $result = str_replace("/>" . $postCaption, $dimension . " />" . $postCaption, $result);

        }
    }

    $contArray['result'] = true;
    $contArray['content_body'] = $result;

    return $contArray;
}


