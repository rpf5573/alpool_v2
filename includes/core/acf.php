<?php
class AP_ACF {

  public static $fields = array( 'year', 'session', 'inspection_check', 'price' );

  public static function add_question_filter() {
    $year_filter_range = ap_opt('year_filter_range');
    $session_filter_range = ap_opt('session_filter_range');

    $year_filter_choices = array();
    foreach( $year_filter_range as $value ) {
      $year_filter_choices[ "{$value}" ] = $value;
    }

    $session_filter_choices = array();
    foreach( $session_filter_range as $value ) {
      $session_filter_choices[ "{$value}" ] = $value;
    }

    acf_add_local_field_group(array(
      'key' => 'question_filter_group',
      'title' => '년도 & 회차',
      'fields' => array(
        array(
          'key' => 'year',
          'label' => '년도',
          'name' => 'year',
          'type' => 'select',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array(
            'width' => '50',
            'class' => '',
            'id' => '',
          ),
          'choices' => $year_filter_choices,
          'default_value' => array(
          ),
          'allow_null' => 1,
          'multiple' => 0,
          'ui' => 1,
          'ajax' => 0,
          'return_format' => 'value',
          'placeholder' => '',
        ),
        array(
          'key' => 'session',
          'label' => '회차',
          'name' => 'session',
          'type' => 'select',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array(
            'width' => '50',
            'class' => '',
            'id' => '',
          ),
          'choices' => $session_filter_choices,
          'default_value' => array(
          ),
          'allow_null' => 1,
          'multiple' => 0,
          'ui' => 1,
          'ajax' => 0,
          'return_format' => 'value',
          'placeholder' => '',
        ),
      ),
      'location' => array(
        array(
          array(
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'question',
          ),
        ),
      ),
      'menu_order' => 200,
      'position' => 'side',
      'style' => 'default',
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'hide_on_screen' => '',
      'active' => 1,
      'description' => '',
    ));
  }

  public static function add_page_banner() {
    acf_add_local_field_group(array(
      'key' => 'page_banner',
      'title' => '배너',
      'fields' => array(
        array(
          'key' => 'page_banner__main',
          'label' => '메인 타이틀',
          'name' => 'page_banner__main',
          'type' => 'text',
          'instructions' => '베너의 메인 타이틀을 설정합니다',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array(
            'width' => '',
            'class' => '',
            'id' => '',
          ),
          'default_value' => '',
          'placeholder' => '',
          'prepend' => '',
          'append' => '',
          'maxlength' => '',
        ),
        array(
          'key' => 'page_banner__sub',
          'label' => '서브 타이틀',
          'name' => 'page_banner__sub',
          'type' => 'text',
          'instructions' => '베너의 서브 타이틀을 설정합니다',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array(
            'width' => '',
            'class' => '',
            'id' => '',
          ),
          'default_value' => '',
          'placeholder' => '',
          'prepend' => '',
          'append' => '',
          'maxlength' => '',
        ),
      ),
      'location' => array(
        array(
          array(
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'page',
          ),
        ),
      ),
      'menu_order' => 0,
      'position' => 'normal',
      'style' => 'default',
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'hide_on_screen' => '',
      'active' => 1,
      'description' => '',
    ));
  }

  public static function add_expert_categories() {
    acf_add_local_field_group(array(
      'key' => 'group_5b3dbb04c1ce7',
      'title' => '유저 페이지',
      'fields' => array(
        array(
          'key' => 'field_5b3dbb19dd023',
          'label' => '카테고리별 관리자 지정',
          'name' => 'expert_categories',
          'type' => 'taxonomy',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array(
            'width' => '',
            'class' => '',
            'id' => '',
          ),
          'taxonomy' => 'question_category',
          'field_type' => 'multi_select',
          'allow_null' => 0,
          'add_term' => 0,
          'save_terms' => 0,
          'load_terms' => 0,
          'return_format' => 'id',
          'multiple' => 0,
        ),
      ),
      'location' => array(
        array(
          array(
            'param' => 'user_role',
            'operator' => '==',
            'value' => 'ap_expert',
          ),
          array(
            'param' => 'user_form',
            'operator' => '==',
            'value' => 'edit',
          ),
          array(
            'param' => 'current_user_role',
            'operator' => '==',
            'value' => 'administrator',
          ),
          array(
            'param' => 'current_user',
            'operator' => '==',
            'value' => 'viewing_back',
          ),
        ),
      ),
      'menu_order' => 0,
      'position' => 'normal',
      'style' => 'default',
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'hide_on_screen' => '',
      'active' => 1,
      'description' => '',
    ));    
  }

  public static function add_question_choices_answer() {
    acf_add_local_field_group(array(
      'key' => 'group_5b3f1f0af36cc',
      'title' => '보기 및 정답',
      'fields' => array(
        array(
          'key' => 'field_5b3f20e2275c2',
          'label' => '보기 및 정답',
          'name' => 'question_choices_answer',
          'type' => 'group',
          'instructions' => '보기와 정답을 입력해 주세요',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array(
            'width' => '',
            'class' => '',
            'id' => '',
          ),
          'layout' => 'row',
          'sub_fields' => array(
            array(
              'key' => 'field_5b3f2101275c3',
              'label' => '보기',
              'name' => 'choices',
              'type' => 'wysiwyg',
              'instructions' => '',
              'required' => 0,
              'conditional_logic' => 0,
              'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
              ),
              'default_value' => '',
              'tabs' => 'all',
              'toolbar' => 'full',
              'media_upload' => 0,
              'delay' => 0,
            ),
            array(
              'key' => 'field_5b3f2137275c4',
              'label' => '정답',
              'name' => 'answer',
              'type' => 'text',
              'instructions' => '',
              'required' => 0,
              'conditional_logic' => 0,
              'wrapper' => array(
                'width' => '',
                'class' => '',
                'id' => '',
              ),
              'default_value' => '',
              'placeholder' => '',
              'prepend' => '',
              'append' => '',
              'maxlength' => '',
            ),
          ),
        ),
      ),
      'location' => array(
        array(
          array(
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'question',
          ),
        ),
      ),
      'menu_order' => 0,
      'position' => 'normal',
      'style' => 'default',
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'hide_on_screen' => '',
      'active' => 1,
      'description' => '',
    ));
  }

  public static function add_inspection_check() {
    acf_add_local_field_group(array(
      'key' => 'group_inspection_check',
      'title' => '검수 완료 확인',
      'fields' => array(
        array(
          'key' => 'inspection_check',
          'label' => '검수완료',
          'name' => 'inspection_check',
          'type' => 'true_false',
          'instructions' => '질문에 달린 답변이 검수완료되지 않았다면, 질문도 검수되지 않은걸로 처리됩니다.',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array(
            'width' => '',
            'class' => '',
            'id' => '',
          ),
          'message' => '',
          'default_value' => 0,
          'ui' => 1,
          'ui_on_text' => '',
          'ui_off_text' => '',
        ),
      ),
      'location' => array(
        array(
          array(
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'question',
          )
        ),
        array(
          array(
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'answer',
          )
        ),
      ),
      'menu_order' => 100,
      'position' => 'side',
      'style' => 'default',
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'hide_on_screen' => '',
      'active' => 1,
      'description' => '',
    ));
  }

  public static function add_price() {
    $min = (int)ap_opt( 'question_price_min' );
    $max = (int)ap_opt( 'question_price_max' );
    acf_add_local_field_group(array(
      'key' => 'group_price',
      'title' => '가격',
      'fields' => array(
        array(
          'key' => 'price',
          'label' => 'price',
          'name' => 'price',
          'type' => 'number',
          'instructions' => '이 질문의 답변을 열람하는데 필요한 포인트입니다(= 질문의 가격)',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array(
            'width' => '',
            'class' => '',
            'id' => '',
          ),
          'default_value' => '',
          'placeholder' => '',
          'prepend' => '',
          'append' => '',
          'min' => $min,
          'max' => $max,
          'step' => 100,
        ),
      ),
      'location' => array(
        array(
          array(
            'param' => 'post_type',
            'operator' => '==',
            'value' => 'question',
          ),
        ),
      ),
      'menu_order' => 300,
      'position' => 'side',
      'style' => 'default',
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'hide_on_screen' => '',
      'active' => 1,
      'description' => '',
    ));
  }

  /**
   * Prevent ACF updating value of fields to wp_postmeta in admin page.
   *
   * @param [type] $value
   * @param [type] $post_id
   * @param [type] $field
   * @return void
   */
  public static function prevent_update_wp_postmeta( $value, $post_id, $field ) {

    if ( isset( $field['name'] ) ) {
      if ( in_array( $field['name'], self::$fields ) ) {
        return;
      }
    }

    return $value;
  }

  public static function load_qameta( $value, $post_id, $field ) {
  
    if ( isset( $field['name'] ) ) {
      if ( in_array( $field['name'], self::$fields ) ) {
        $value = ap_get_post_field( $field['name'], $post_id );
      }
    }

    return $value;
  }

}