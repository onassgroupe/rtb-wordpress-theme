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
		// Compact dans la sidebar (toujours visible) ; le vrai desk s'ouvre en plein écran.
		add_meta_box( 'rtb_live', '🔴 ' . $this->t( 'Direct (live)' ), [ $this, 'render' ], 'post', 'side', 'high' );
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'rtb_live_box', 'rtb_live_box_nonce' );
		$status = Repository::status( $post->ID );
		?>
		<style>
		.rtb-live-overlay{position:fixed;inset:0;z-index:999999;background:rgba(10,10,10,.55);display:none}
		.rtb-live-modal{position:absolute;top:4vh;left:50%;transform:translateX(-50%);width:min(1040px,94vw);height:90vh;background:#fff;border-radius:14px;box-shadow:0 24px 70px rgba(0,0,0,.45);display:flex;flex-direction:column;overflow:hidden}
		.rtb-live-modal-head{display:flex;align-items:center;justify-content:space-between;padding:14px 22px;border-bottom:1px solid #e2e4e7}
		.rtb-live-modal-head h2{margin:0;font-size:17px}
		.rtb-live-modal-close{background:none;border:0;font-size:26px;line-height:1;cursor:pointer;color:#50575e}
		.rtb-live-desk{display:flex;gap:28px;padding:22px;overflow:auto;flex:1;align-items:flex-start}
		.rtb-live-col-form{flex:1 1 360px;max-width:440px}
		.rtb-live-col-list{flex:2 1 380px;min-width:300px}
		.rtb-live-desk select,.rtb-live-desk textarea{width:100%}
		.rtb-live-desk textarea{font-size:14px;line-height:1.5}
		.rtb-live-item{border:1px solid #e2e4e7;border-radius:8px;padding:10px 12px;margin-bottom:8px;background:#fff;font-size:13px;line-height:1.5}
		.rtb-live-item .meta{color:#50575e;font-weight:600}
		.rtb-live-item .tag{display:inline-block;font-size:10px;font-weight:700;color:#fff;background:#E70C2F;border-radius:4px;padding:1px 6px;margin-left:4px;text-transform:uppercase}
		.rtb-live-item .tag.important{background:#C9A227}
		.rtb-live-item .star{color:#C9A227}
		.rtb-live-item img{max-width:200px;border-radius:6px;display:block;margin-top:6px}
		.rtb-live-item .del{color:#b32d2e;text-decoration:none;float:right;font-weight:700}
		</style>

		<p>
			<label for="rtb_live_status"><strong><?php echo esc_html( $this->t( 'Statut' ) ); ?></strong></label><br>
			<select name="rtb_live_status" id="rtb_live_status" style="width:100%">
				<option value="" <?php selected( $status, '' ); ?>><?php echo esc_html( $this->t( 'Article normal' ) ); ?></option>
				<option value="open" <?php selected( $status, 'open' ); ?>><?php echo esc_html( $this->t( 'Direct ouvert' ) ); ?></option>
				<option value="closed" <?php selected( $status, 'closed' ); ?>><?php echo esc_html( $this->t( 'Direct terminé' ) ); ?></option>
			</select>
		</p>
		<p id="rtb_live_hint" class="description"<?php echo $status ? ' style="display:none"' : ''; ?>>
			<?php echo esc_html( $this->t( 'Choisissez « Direct ouvert » pour ouvrir le desk de direct (pensez à enregistrer l\'article).' ) ); ?>
		</p>
		<p id="rtb_live_open_wrap"<?php echo $status ? '' : ' style="display:none"'; ?>>
			<button type="button" class="button button-primary button-hero" id="rtb_live_open" style="width:100%">📡 <?php echo esc_html( $this->t( 'Ouvrir le desk de direct' ) ); ?></button>
		</p>

		<div class="rtb-live-overlay" id="rtb_live_overlay">
			<div class="rtb-live-modal">
				<div class="rtb-live-modal-head">
					<h2>🔴 <?php echo esc_html( $this->t( 'Desk de direct' ) ); ?> — <?php echo esc_html( get_the_title( $post ) ?: $this->t( 'Article' ) ); ?></h2>
					<button type="button" class="rtb-live-modal-close" id="rtb_live_close" aria-label="Fermer">×</button>
				</div>
				<div class="rtb-live-desk">
					<div class="rtb-live-col-form">
						<p><strong><?php echo esc_html( $this->t( 'Ajouter une mise à jour' ) ); ?></strong></p>
						<select id="rtb_live_label" style="margin-bottom:8px">
							<option value=""><?php echo esc_html( $this->t( 'Sans étiquette' ) ); ?></option>
							<option value="flash">FLASH</option>
							<option value="urgent">URGENT</option>
							<option value="important">IMPORTANT</option>
						</select>
						<textarea id="rtb_live_text" rows="5" placeholder="<?php echo esc_attr( $this->t( 'Texte de la mise à jour…' ) ); ?>"></textarea>
						<p style="display:flex;align-items:center;gap:8px;margin:10px 0">
							<button type="button" class="button" id="rtb_live_img_btn" style="flex:1"><?php echo esc_html( $this->t( 'Ajouter une image' ) ); ?></button>
							<a href="#" id="rtb_live_img_clear" style="display:none;color:#b32d2e;text-decoration:none">×</a>
						</p>
						<input type="hidden" id="rtb_live_img">
						<img id="rtb_live_img_prev" src="" alt="" style="display:none;width:100%;border-radius:6px;margin-bottom:8px">
						<p><label><input type="checkbox" id="rtb_live_key"> <?php echo esc_html( $this->t( 'Épingler comme point clé' ) ); ?></label></p>
						<p><button type="button" class="button button-primary button-hero" id="rtb_live_add" style="width:100%"><?php echo esc_html( $this->t( 'Publier la mise à jour' ) ); ?></button></p>
					</div>
					<div class="rtb-live-col-list">
						<p><strong><?php echo esc_html( $this->t( 'Mises à jour publiées' ) ); ?></strong></p>
						<div id="rtb_live_list"></div>
					</div>
				</div>
			</div>
		</div>

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
				var L=document.getElementById('rtb_live_list');
				if(!items||!items.length){ L.innerHTML='<p style="color:#787c82">Aucune mise à jour pour l\'instant.</p>'; return; }
				L.innerHTML=items.map(function(e){
					var tag=e.label?('<span class="tag'+(e.label==='important'?' important':'')+'">'+e.label+'</span>'):'';
					var star=e.key?'<span class="star">★ </span>':'';
					var img=e.img?('<img src="'+e.img+'">'):'';
					return '<div class="rtb-live-item"><a href="#" class="del" data-del="'+e.id+'">×</a><div class="meta">'+star+new Date(e.t*1000).toLocaleTimeString()+tag+'</div>'+e.text+img+'</div>';
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

			// Ouverture/fermeture du desk + bascule selon le statut (sans recharger la page).
			var sel=document.getElementById('rtb_live_status'),
			    openWrap=document.getElementById('rtb_live_open_wrap'),
			    hint=document.getElementById('rtb_live_hint'),
			    overlay=document.getElementById('rtb_live_overlay');
			function toggle(){ var on=sel.value!==''; openWrap.style.display=on?'':'none'; hint.style.display=on?'none':''; }
			sel.addEventListener('change',toggle); toggle();
			// Sort l'overlay du contexte d'empilement de la boîte méta (sinon l'en-tête/sidebar Gutenberg passent au-dessus).
			document.getElementById('rtb_live_open').addEventListener('click',function(){ if(overlay.parentNode!==document.body){ document.body.appendChild(overlay); } overlay.style.display='block'; load(); });
			document.getElementById('rtb_live_close').addEventListener('click',function(){ overlay.style.display='none'; });
			overlay.addEventListener('click',function(e){ if(e.target===overlay) overlay.style.display='none'; });

			load();
		})();
		</script>
		<?php
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
