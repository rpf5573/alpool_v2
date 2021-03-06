<?php
$user_id = $template_args['user_data']->ID;
$args = wp_json_encode(
  [
    '__nonce' => wp_create_nonce( 'user_info_edit_' . $user_id ),
    'user_id'      => $user_id,
    'ap_ajax_action' => 'update_user_nickname',
  ]
);?>
<div class="ui mini modal ap-user-info-edit-modal --nickname">
  <div class="header">
    닉네임 변경
  </div>
  <div class="content">
    <p> 닉네임을 입력해 주세요 </p>
    <div class="ui input">
      <input type="text" placeholder="닉네임">
    </div>
  </div>
  <div class="actions">
    <div class="ui negative button">
      취소
    </div>
    <div class="ui positive button" apquery="<?=esc_js( $args )?>">
      확인
    </div>
  </div>
</div>