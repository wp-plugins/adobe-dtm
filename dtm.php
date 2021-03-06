<?php
global $dtm;

function SDIDTM_checked( $args ){
  if($args){
    $v = $args['value'];
    if($v == 1 || $v == '1' || $v == 'on'){
      return true;
    }
    else {
      return false;
    }
  }
  else {
    return false;
  }
}

function SDIDTM_disable(){
  global $dtm, $current_user, $wp_admin_bar;
  $isDisabled = false;
  $disable = SDIDTM_get_options('disable');
  $perms = array();
  $guest = array();

  foreach($disable as $d){
    if(SDIDTM_checked($d)){
      if(in_array($d['code'], $current_user->roles)){
        $isDisabled = true;
      }
    }
    if($d['code'] == 'guest' && count($current_user->roles) == 0){
      if(SDIDTM_checked($v)){
        $isDisabled = true;
      }
    }
  }

  if($isDisabled){
    // notify the logged in user in the admin bar that DTM is disabled
    if($wp_admin_bar){
      $wp_admin_bar->add_menu(
        array(
          'id'=>'adobe-dtm',
          'title'=>'Adobe DTM Disabled'
        )
      );
    }
  }

  // disable for guests
  if(!$isDisabled && SDIDTM_checked($guest) && count($current_user->roles) == 0){
    $isDisabled = true;
  }

  return $isDisabled;
}

function SDI_dtm_exists(){
  global $config;
  if($SDIDTM_options[SDIDTM_OPTION_DTM_EXISTS]){
    return true;
  }
  else {
    return false;
  }
}

function SDIDTM_get_name($name){
  global $SDIDTM_defaultoptions, $SDIDTM_options;
  $value = $SDIDTM_options[$name];
  if(!$value || $value == ''){
    $value = $SDIDTM_defaultoptions[$name];
  }
  return $value;
}

function SDIDTM_include( $value ){
  if($value === 1 || $value == '1' || $value === true || $value == 'true'){
    return true;
  }
  else {
    return false;
  }
}

function SDIDTM_value( $field ){
  global $dtmSaved;
  if(isset($field['name'])){
    return $dtmSaved['name-'.$field['name']];
  }
}

function SDIDTM_add_datalayer($dataLayer) {
  global $current_user, $wp_query, $dtmSaved;
  $config = SDIDTM_get_options('config', true);
  $data = SDIDTM_get_options('dataLayer', true);
  $s = $dtmSaved;

  $postType = SDIDTM_include($s['include-posttype']);
  $postLbl = SDIDTM_value($data['posttype']);
  $subPostType = SDIDTM_include($s['include-postsubtype']);
  $subPostLbl = SDIDTM_value($data['postsubtype']);

  if(SDIDTM_disable()){
    return array();
  }

  $date = array();
  
  if (SDIDTM_include($s['include-loggedin'])) {
    if (is_user_logged_in()) {
      $dataLayer[SDIDTM_value($data['loggedin'])] = "logged-in";
    } else {
      $dataLayer[SDIDTM_value($data['loggedin'])] = "logged-out";
    }
  } 

  if (SDIDTM_include($s['include-userrole'])) {
    get_currentuserinfo();
    $dataLayer[SDIDTM_value($data['userrole'])] = ($current_user->roles[0] == NULL ? "guest" : $current_user->roles[0]);
  }
  
  if (SDIDTM_include($s['include-posttitle'])) {
    $dataLayer[SDIDTM_value($data['posttitle'])] = strip_tags(wp_title("|", false, "right"));
  }

  if (is_singular()) {
    if(get_the_ID() && SDIDTM_include($s['include-pageid'])){
      $dataLayer[SDIDTM_value($data['pageid'])] = get_the_ID();
    }

    if(SDIDTM_include($s['include-custom'])){
      $meta = get_post_custom();
      $newmeta = array();
      foreach($meta as $mn=>$mv){
        if(strpos($mn, "_edit_")===false && strpos($mn, "_wp_")===false){
          $newmeta[$mn] = $mv;
        }
      }
      $dataLayer[SDIDTM_value($data['custom'])] = $newmeta;
    }

    if ($postType) {
      $dataLayer[$postLbl] = get_post_type();
    }
    if($subPostType) {
      $dataLayer[$subPostLbl] = "single-" . get_post_type();
    }

    if(SDIDTM_include($s['include-comments'])){
      if(comments_open()){
        $dataLayer[SDIDTM_value($data['comments'])] = get_comments_number();
      }
    }
    
    if (SDIDTM_include($s['include-categories'])) {
      $_post_cats = get_the_category();
      if ($_post_cats) {
        $dataLayer[SDIDTM_value($data['categories'])] = array();
        foreach ($_post_cats as $_one_cat) {
          $dataLayer[SDIDTM_value($data['categories'])][] = $_one_cat->slug;
        }
      }
    }
    
    if (SDIDTM_include($s['include-tags'])) {
      $_post_tags = get_the_tags();
      if ($_post_tags) {
        $dataLayer[SDIDTM_value($data['tags'])] = array();
        foreach ($_post_tags as $tag) {
          $dataLayer[SDIDTM_value($data['tags'])][] = $tag->slug;
        }
      }
    }
    
    if (SDIDTM_include($s['include-author'])) {
      $postuser = get_userdata($GLOBALS["post"]->post_author);
      if (false !== $postuser) {
        $dataLayer[SDIDTM_value($data['author'])] = $postuser->display_name;
      }
    }
    
    $date["date"] = get_the_date();
    $date["year"] = get_the_date("Y");
    $date["month"] = get_the_date("m");
    $date["day"] = get_the_date("d");
  }
  
  if (is_archive() || is_post_type_archive()) {
    if ($postType) {
      $dataLayer[$postLbl] = get_post_type();
      
      if (is_category()) {
        $dataLayer[$subPostLbl] = "category-" . get_post_type();
      } else if (is_tag()) {
        $dataLayer[$subPostLbl] = "tag-" . get_post_type();
      } else if (is_tax()) {
        $dataLayer[$subPostLbl] = "tax-" . get_post_type();
      } else if (is_author()) {
        $dataLayer[$subPostLbl] = "author-" . get_post_type();
      } else if (is_year()) {
        $dataLayer[$subPostLbl] = "year-" . get_post_type();
        
        $date["year"] = get_the_date("Y");
      } else if (is_month()) {
        $dataLayer[$subPostLbl] = "month-" . get_post_type();
        $date["year"] = get_the_date("Y");
        $date["month"] = get_the_date("m");
      } else if (is_day()) {
        $dataLayer[$subPostLbl] = "day-" . get_post_type();
        
        $date["date"] = get_the_date();
        $date["year"] = get_the_date("Y");
        $date["month"] = get_the_date("m");
        $date["day"] = get_the_date("d");
      } else if (is_time()) {
        $dataLayer[$subPostLbl] = "time-" . get_post_type();
      } else if (is_date()) {
        $dataLayer[$subPostLbl] = "date-" . get_post_type();
        
        $date["date"] = get_the_date();
        $date["year"] = get_the_date("Y");
        $date["month"] = get_the_date("m");
        $date["day"] = get_the_date("d");
      }
    }
    
    if ((is_tax() || is_category()) && $SDIDTM_options[SDIDTM_include($s['include-categories'])]) {
      $_post_cats = get_the_category();
      $dataLayer[SDIDTM_value($data['categories'])] = array();
      foreach ($_post_cats as $_one_cat) {
        $dataLayer[SDIDTM_value($data['categories'])][] = $_one_cat->slug;
      }
    }
    
    if (SDIDTM_include($s['include-author']) && (is_author())) {
      $dataLayer[SDIDTM_value($data['author'])] = get_the_author();
    }
  }
  
  if (is_search()) {
    if(SDIDTM_include($s['include-searchterm'])){
      $dataLayer[SDIDTM_value($data['searchterm'])] = get_search_query();
    }
    if(SDIDTM_include($s['include-searchorigin'])){
      $dataLayer[SDIDTM_value($data['searchorigin'])] = $_SERVER["HTTP_REFERER"];
    }
    if(SDIDTM_include($s['include-searchresults'])){
      $dataLayer[SDIDTM_value($data['searchresults'])] = $wp_query->post_count;
    }
  }
  
  if (is_front_page() && $postType) {
    $dataLayer[$postLbl] = "homepage";
  }
  
  if (!is_front_page() && is_home() && $postType) {
    $dataLayer[$postLbl] = "blog-home";
  }
  
  if (SDIDTM_include($s['include-postcount'])) {
    $dataLayer[SDIDTM_value($data['postcount'])] = (int)$wp_query->post_count;
    // $dataLayer["postCountTotal"] = (int)$wp_query->found_posts;
  }

  if (SDIDTM_include($s['include-postdate']) && count($date)>0) {
    $dataLayer[SDIDTM_value($data['postdate'])] = $date;
  }
  
  return $dataLayer;
}

function SDIDTM_wp_header() {
  global $dtm;
  $config = SDIDTM_get_options('config', true);
  
  $dataLayer = array();
  $dataLayer = (array)apply_filters("sdidtm_build_datalayer", $dataLayer);
  
  $_dtm_header_content = '';
  
  if ($config['dtm-code']['value'] != "" && !SDIDTM_disable()) {
    $_dtm_header_content.= '
<script type="text/javascript">
var ' . $config['dtm-datalayer-variable-name']['value'] . ' = ' . json_encode($dataLayer) . ';
</script>';

    if(!SDIDTM_checked($config['include-dtm-exists'])){
      $_dtm_header_content.= '
<script type="text/javascript" src="' . $config['dtm-code']['value'] . '"></script>';
    }
  }
  
  echo $_dtm_header_content;
}


function SDIDTM_wp_footer() {
  global $dtm;
  $config = SDIDTM_get_options('config', true);
  
  $_dtm_tag = '';
  
  if ($config['dtm-code']['value'] != "" && !SDIDTM_checked($config['include-dtm-exists']) && !SDIDTM_disable()) {
    $_dtm_tag.= '
<script type="text/javascript">
if(typeof _satellite != "undefined"){
  _satellite.pageBottom();
}
</script>';
  }
  
  echo $_dtm_tag;
}

add_action("wp_head", "SDIDTM_wp_header", 1);
add_action("wp_footer", "SDIDTM_wp_footer", 100000);
add_filter("sdidtm_build_datalayer", "SDIDTM_add_datalayer");
