<?php

namespace RTB\LiveBlog;

defined( 'ABSPATH' ) || exit;

/** Méta-box d'édition du direct + endpoints AJAX d'admin. */
final class Admin {

	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'metabox' ] );
		add_action( 'save_post_post', [ $this, 'saveStatus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'media' ] );
		add_action( 'wp_ajax_rtb_live_list', [ $this, 'ajaxList' ] );
		add_action( 'wp_ajax_rtb_live_add', [ $this, 'ajaxAdd' ] );
		add_action( 'wp_ajax_rtb_live_del', [ $this, 'ajaxDel' ] );
	}

	/** Charge le sélecteur de média sur l'écran d'édition d'article. */
	public function media( string $hook ): void {
		if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			wp_enqueue_media();
		}
	}

	private function t( string $s ): string {
		return function_exists( 'rtb_t' ) ? rtb_t( $s ) : $s;
	}

	public function metabox(): void {
		add_meta_box( 'rtb_live', '🔴 ' . $this->t( 'Direct (live)' ), [ $this, 'render' ], 'post', 'side', 'high' );
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'rtb_live_box', 'rtb_live_box_nonce' );
		$status = Repository::status( $post->ID );
		?>
		<p>
			<label for="rtb_live_status"><strong><?php echo esc_html( $this->t( 'Statut' ) ); ?></strong></label><br>
			<select name="rtb_live_status" id="rtb_live_status" style="width:100%">
				<option value="" <?php selected( $status, '' ); ?>><?php echo esc_html( $this->t( 'Article normal' ) ); ?></option>
				<option value="open" <?php selected( $status, 'open' ); ?>><?php echo esc_html( $this->t( 'Direct ouvert' ) ); ?></option>
				<option value="closed" <?php selected( $status, 'closed' ); ?>><?php echo esc_html( $this->t( 'Direct terminé' ) ); ?></option>
			</select>
		</p>
		<?php if ( $status ) : ?>
			<hr>
			<p><strong><?php echo esc_html( $this->t( 'Ajouter une mise à jour' ) ); ?></strong></p>
			<select id="rtb_live_label" style="width:100%;margin-bottom:6px">
				<option value=""><?php echo esc_html( $this->t( 'Sans étiquette' ) ); ?></option>
				<option value="flash">FLASH</option>
				<option value="urgent">URGENT</option>
				<option value="important">IMPORTANT</option>
			</select>
			<textarea id="rtb_live_text" rows="3" style="width:100%" placeholder="<?php echo esc_attr( $this->t( 'Texte de la mise à jour…' ) ); ?>"></textarea>
			<p style="display:flex;align-items:center;gap:8px;margin:6px 0">
				<button type="button" class="button" id="rtb_live_img_btn" style="flex:1"><?php echo esc_html( $this->t( 'Ajouter une image' ) ); ?></button>
				<a href="#" id="rtb_live_img_clear" style="display:none;color:#b32d2e;text-decoration:none">×</a>
			</p>
			<input type="hidden" id="rtb_live_img">
			<img id="rtb_live_img_prev" src="" alt="" style="display:none;width:100%;border-radius:6px;margin-bottom:6px">
			<p><label style="font-size:12px"><input type="checkbox" id="rtb_live_key"> <?php echo esc_html( $this->t( 'Épingler comme point clé' ) ); ?></label></p>
			<p><button type="button" class="button button-primary" id="rtb_live_add" style="width:100%"><?php echo esc_html( $this->t( 'Publier la mise à jour' ) ); ?></button></p>
			<div id="rtb_live_list"></div>
			<script>
			(function(){
				var pid=<?php echo (int) $post->ID; ?>, nonce='<?php echo esc_js( wp_create_nonce( 'rtb_live_ajax' ) ); ?>', ajax='<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
				var imgInput=document.getElementById('rtb_live_img'), imgPrev=document.getElementById('rtb_live_img_prev'), imgClear=document.getElementById('rtb_live_img_clear'), frame;
				function setImg(url){ imgInput.value=url||''; if(url){imgPrev.src=url;imgPrev.style.display='block';imgClear.style.display='inline';}else{imgPrev.style.display='none';imgClear.style.display='none';} }
				document.getElementById('rtb_live_img_btn').addEventListener('click',function(e){ e.preventDefault();
					if(frame){frame.open();return;}
					frame=wp.media({title:'Image du point',multiple:false,library:{type:'image'}});
					frame.on('select',function(){ setImg(frame.state().get('selection').first().toJSON().url); });
					frame.open();
				});
				imgClear.addEventListener('click',function(e){ e.preventDefault(); setImg(''); });
				function render(items){
					document.getElementById('rtb_live_list').innerHTML=(items||[]).map(function(e){
						return '<div style="border-top:1px solid #eee;padding:6px 0;font-size:12px">'+(e.img?('<img src="'+e.img+'" style="width:100%;border-radius:4px;margin-bottom:4px">'):'')+'<b>'+new Date(e.t*1000).toLocaleTimeString()+'</b> '+(e.key?'★ ':'')+(e.label?('['+e.label+'] '):'')+e.text+' <a href="#" data-del="'+e.id+'" style="color:#b32d2e">×</a></div>';
					}).join('');
				}
				function load(){ fetch(ajax+'?action=rtb_live_list&id='+pid+'&_n='+nonce).then(function(r){return r.json();}).then(function(d){ if(d.success) render(d.data.entries); }); }
				document.getElementById('rtb_live_add').addEventListener('click',function(){
					var fd=new FormData(); fd.append('action','rtb_live_add'); fd.append('id',pid); fd.append('_n',nonce);
					fd.append('label',document.getElementById('rtb_live_label').value); fd.append('text',document.getElementById('rtb_live_text').value);
					fd.append('img',imgInput.value); fd.append('key',document.getElementById('rtb_live_key').checked?'1':'0');
					fetch(ajax,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){ if(d.success){document.getElementById('rtb_live_text').value='';document.getElementById('rtb_live_key').checked=false;setImg('');render(d.data.entries);} else alert(d.data&&d.data.message||'Erreur'); });
				});
				document.getElementById('rtb_live_list').addEventListener('click',function(e){
					var id=e.target.getAttribute('data-del'); if(!id)return; e.preventDefault(); if(!confirm('Supprimer ?'))return;
					var fd=new FormData(); fd.append('action','rtb_live_del'); fd.append('id',pid); fd.append('eid',id); fd.append('_n',nonce);
					fetch(ajax,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){ if(d.success) render(d.data.entries); });
				});
				load();
			})();
			</script>
		<?php else : ?>
			<p class="description"><?php echo esc_html( $this->t( 'Choisissez « Direct ouvert » puis enregistrez pour activer les mises à jour en temps réel.' ) ); ?></p>
		<?php endif;
	}

	public function saveStatus( int $post_id ): void {
		if ( ! isset( $_POST['rtb_live_box_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['rtb_live_box_nonce'] ), 'rtb_live_box' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		Repository::setStatus( $post_id, isset( $_POST['rtb_live_status'] ) ? sanitize_key( wp_unslash( $_POST['rtb_live_status'] ) ) : '' );
	}

	private function guard( int $post_id ): void {
		if ( ! check_ajax_referer( 'rtb_live_ajax', '_n', false ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => 'non autorisé' ], 403 );
		}
	}

	public function ajaxList(): void {
		$id = (int) ( $_REQUEST['id'] ?? 0 );
		$this->guard( $id );
		wp_send_json_success( [ 'entries' => Repository::entries( $id ) ] );
	}

	public function ajaxAdd(): void {
		$id = (int) ( $_POST['id'] ?? 0 );
		$this->guard( $id );
		$text = trim( (string) wp_unslash( $_POST['text'] ?? '' ) );
		if ( '' === $text ) {
			wp_send_json_error( [ 'message' => 'Texte vide.' ] );
		}
		$img = esc_url_raw( (string) wp_unslash( $_POST['img'] ?? '' ) );
		$key = '1' === (string) ( $_POST['key'] ?? '' );
		wp_send_json_success( [ 'entries' => Repository::add( $id, $text, (string) wp_unslash( $_POST['label'] ?? '' ), $img, $key ) ] );
	}

	public function ajaxDel(): void {
		$id = (int) ( $_POST['id'] ?? 0 );
		$this->guard( $id );
		wp_send_json_success( [ 'entries' => Repository::delete( $id, (int) ( $_POST['eid'] ?? 0 ) ) ] );
	}
}
