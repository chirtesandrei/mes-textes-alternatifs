<?php
if ( ! defined('ABSPATH') ) { exit; }
final class MSTA_Plugin {
	const PAGE='mes-textes-alternatifs';
	public static function boot() {
		add_action('admin_menu',function(){add_management_page('Mes Textes alternatifs','Mes Textes alternatifs','manage_options',self::PAGE,array(__CLASS__,'render'));});
		add_action('admin_post_msta_save',array(__CLASS__,'save'));
	}
	public static function save() {
		$id=absint(MesPlugins_Tools_V1::field('attachment_id'));
		MesPlugins_Tools_V1::guard('msta_save_' . $id);
		$post=get_post($id);
		if(!$post || 'attachment'!==$post->post_type || !wp_attachment_is_image($id) || !current_user_can('edit_post',$id)) { MesPlugins_Tools_V1::end(self::PAGE,'invalid'); }
		$text=sanitize_text_field(MesPlugins_Tools_V1::field('alt'));
		$decorative='1'===MesPlugins_Tools_V1::field('decorative');
		if(strlen($text)>4000 || (''===$text && !$decorative) || (''!==$text && $decorative)) { MesPlugins_Tools_V1::end(self::PAGE,'invalid'); }
		update_post_meta($id,'_wp_attachment_image_alt',$text);
		if($decorative) { update_post_meta($id,'_msta_decorative',1); } else { delete_post_meta($id,'_msta_decorative'); }
		MesPlugins_Tools_V1::end(self::PAGE,'saved');
	}
	public static function render() {
		MesPlugins_Tools_V1::head('Mes Textes alternatifs');
		echo '<p>Relisez et corrigez les textes alternatifs dans la bibliothèque. Une image décorative peut avoir un texte vide ; une image informative doit être décrite selon son rôle dans la page.</p><p><strong>Limite importante :</strong> les textes déjà enregistrés dans le HTML des articles, des blocs ou d’un constructeur ne sont pas réécrits. Vérifiez les pages concernées après vos changements. Aucune conformité d’accessibilité n’est garantie par ce seul outil.</p>';
		$paged=isset($_GET['mp_page']) && is_scalar($_GET['mp_page']) ? max(1,min(100000,absint($_GET['mp_page']))) : 1;
		$q=new WP_Query(array('post_type'=>'attachment','post_status'=>array('inherit','private','publish'),'post_mime_type'=>'image','posts_per_page'=>20,'paged'=>$paged,'orderby'=>'ID','order'=>'DESC'));
		echo '<table class="widefat striped"><thead><tr><th>Image</th><th>Texte alternatif de la bibliothèque</th></tr></thead><tbody>';
		foreach($q->posts as $post) {
			$id=$post->ID; $alt=get_post_meta($id,'_wp_attachment_image_alt',true); $decorative=(bool)get_post_meta($id,'_msta_decorative',true);
			echo '<tr><td>' . wp_get_attachment_image($id,array(100,100)) . '<br><a href="' . esc_url(get_edit_post_link($id)) . '">' . esc_html(get_the_title($id)) . '</a></td><td>';
			echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="msta_save"><input type="hidden" name="attachment_id" value="' . esc_attr((string)$id) . '">';
			wp_nonce_field('msta_save_' . $id);
			echo '<label for="msta-alt-' . esc_attr((string)$id) . '">Description</label><br><textarea rows="2" cols="60" maxlength="1000" id="msta-alt-' . esc_attr((string)$id) . '" name="alt">' . esc_textarea(is_string($alt)?$alt:'') . '</textarea><p><label><input type="checkbox" name="decorative" value="1" ' . checked($decorative,true,false) . '> Marquer comme décorative pour la bibliothèque (texte vide)</label></p><button class="button" type="submit">Enregistrer</button></form></td></tr>';
		}
		echo '</tbody></table>';
		for($p=max(1,$paged-1);$p<=min($q->max_num_pages,$paged+1);++$p) { echo '<a class="button" href="' . esc_url(add_query_arg(array('page'=>self::PAGE,'mp_page'=>$p),admin_url('tools.php'))) . '">' . esc_html((string)$p) . '</a> '; }
		echo '<p>Aucun service d’IA, aucun envoi d’image, aucun remplissage automatique à partir d’un nom de fichier. Les textes saisis sont conservés lors de la désinstallation.</p></div>';
	}
}
