<?php
if (!defined('ABSPATH')) exit;

function ens_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array('height'=>120,'width'=>320,'flex-height'=>true,'flex-width'=>true));
    add_theme_support('html5', array('search-form','comment-form','comment-list','gallery','caption','style','script'));
    register_nav_menus(array('primary'=>'Menú principal','footer'=>'Menú del pie'));
}
add_action('after_setup_theme','ens_setup');

function ens_enqueue_assets() {
    wp_enqueue_style('ens-style', get_stylesheet_uri(), array(), '1.1.0');
    wp_enqueue_script('ens-cart', get_template_directory_uri().'/assets/js/cart.js', array(), '1.1.0', true);
    $products = array();
    $product_query = new WP_Query(array('post_type'=>'ens_product','posts_per_page'=>-1,'post_status'=>'publish','no_found_rows'=>true));
    if ($product_query->have_posts()) {
        while ($product_query->have_posts()) { $product_query->the_post();
            $products[(string)get_the_ID()] = array('name'=>get_the_title(),'price'=>(float)get_post_meta(get_the_ID(),'_ens_price',true));
        }
        wp_reset_postdata();
    }
    wp_localize_script('ens-cart','ENS_DATA',array(
        'whatsapp'=>preg_replace('/\\D+/','',get_theme_mod('ens_whatsapp','5491100000000')),
        'cartUrl'=>get_permalink(get_page_by_path('carrito')) ?: home_url('/carrito/'),
        'currency'=>'$ ',
        'products'=>$products
    ));
}
add_action('wp_enqueue_scripts','ens_enqueue_assets');

function ens_register_product_type() {
    register_post_type('ens_product',array(
        'labels'=>array('name'=>'Productos','singular_name'=>'Producto','add_new'=>'Agregar producto','add_new_item'=>'Agregar producto','edit_item'=>'Editar producto','new_item'=>'Nuevo producto','view_item'=>'Ver producto','search_items'=>'Buscar productos','not_found'=>'No hay productos'),
        'public'=>true,'show_in_rest'=>true,'menu_icon'=>'dashicons-hammer','supports'=>array('title','editor','thumbnail','excerpt'),'has_archive'=>'catalogo','rewrite'=>array('slug'=>'producto'),
        'taxonomies'=>array('ens_product_cat')
    ));
    register_taxonomy('ens_product_cat','ens_product',array('labels'=>array('name'=>'Categorías','singular_name'=>'Categoría','add_new_item'=>'Agregar categoría','menu_name'=>'Categorías'),'public'=>true,'show_in_rest'=>true,'hierarchical'=>true,'rewrite'=>array('slug'=>'categoria')));
    register_post_type('ens_feature',array(
        'labels'=>array('name'=>'Accesos rápidos','singular_name'=>'Acceso rápido','add_new'=>'Agregar acceso','add_new_item'=>'Agregar acceso rápido','edit_item'=>'Editar acceso rápido','new_item'=>'Nuevo acceso rápido','search_items'=>'Buscar accesos','not_found'=>'No hay accesos'),
        'public'=>false,'show_ui'=>true,'show_in_menu'=>'edit.php?post_type=ens_product','show_in_rest'=>true,'menu_icon'=>'dashicons-grid-view','supports'=>array('title','excerpt','thumbnail','page-attributes')
    ));
}
add_action('init','ens_register_product_type');

function ens_product_meta_box() { add_meta_box('ens_product_details','Datos del producto','ens_product_meta_html','ens_product','normal','high'); }
add_action('add_meta_boxes','ens_product_meta_box');
function ens_product_meta_html($post) {
    wp_nonce_field('ens_product_save','ens_product_nonce');
    $price=get_post_meta($post->ID,'_ens_price',true); $sku=get_post_meta($post->ID,'_ens_sku',true); $featured=get_post_meta($post->ID,'_ens_featured',true);
    $stock=get_post_meta($post->ID,'_ens_stock',true) ?: 'En stock'; $brand=get_post_meta($post->ID,'_ens_brand',true); $unit=get_post_meta($post->ID,'_ens_unit',true); $specs=get_post_meta($post->ID,'_ens_specs',true); $gallery=get_post_meta($post->ID,'_ens_gallery',true);
    echo '<div class="ens-admin-product-grid"><div><p><label><strong>Precio</strong><br><input type="number" step="0.01" min="0" name="ens_price" value="'.esc_attr($price).'" style="width:220px"></label></p><p><label><strong>SKU / código interno</strong><br><input type="text" name="ens_sku" value="'.esc_attr($sku).'" style="width:320px"></label></p><p><label><strong>Marca</strong><br><input type="text" name="ens_brand" value="'.esc_attr($brand).'" style="width:320px"></label></p><p><label><strong>Unidad de venta</strong><br><input type="text" name="ens_unit" value="'.esc_attr($unit).'" placeholder="unidad, caja, metro..." style="width:320px"></label></p></div><div><p><label><strong>Disponibilidad</strong><br><select name="ens_stock" style="width:220px"><option '.selected($stock,'En stock',false).'>En stock</option><option '.selected($stock,'Consultar',false).'>Consultar</option><option '.selected($stock,'Agotado',false).'>Agotado</option></select></label></p><p><label><input type="checkbox" name="ens_featured" value="1" '.checked($featured,'1',false).'> Producto destacado</label></p><p><label><strong>Especificaciones</strong><br><textarea name="ens_specs" rows="6" style="width:100%;max-width:420px" placeholder="Material|Acero forjado&#10;Uso|Obra y jardín&#10;Garantía|12 meses">'.esc_textarea($specs).'</textarea></label></p></div></div><hr><p><strong>Galería del producto</strong></p><input type="hidden" name="ens_gallery" id="ens_gallery" value="'.esc_attr($gallery).'"> <button type="button" class="button ens-gallery-button">Elegir imágenes</button><div id="ens_gallery_preview" class="ens-gallery-preview">';
    if ($gallery) { foreach(array_filter(array_map('absint',explode(',',$gallery))) as $image_id) { $src=wp_get_attachment_image_url($image_id,'thumbnail'); if($src) echo '<img src="'.esc_url($src).'" alt="">'; } }
    echo '</div><p class="description">La imagen destacada se usa como principal. La galería permite agregar vistas adicionales.</p>';
}
function ens_save_product_meta($post_id) {
    if (!isset($_POST['ens_product_nonce']) || !wp_verify_nonce($_POST['ens_product_nonce'],'ens_product_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post',$post_id)) return;
    update_post_meta($post_id,'_ens_price',isset($_POST['ens_price']) ? sanitize_text_field($_POST['ens_price']) : '');
    update_post_meta($post_id,'_ens_sku',isset($_POST['ens_sku']) ? sanitize_text_field($_POST['ens_sku']) : '');
    update_post_meta($post_id,'_ens_featured',isset($_POST['ens_featured']) ? '1' : '0');
    update_post_meta($post_id,'_ens_stock',isset($_POST['ens_stock']) ? sanitize_text_field($_POST['ens_stock']) : 'En stock');
    update_post_meta($post_id,'_ens_brand',isset($_POST['ens_brand']) ? sanitize_text_field($_POST['ens_brand']) : '');
    update_post_meta($post_id,'_ens_unit',isset($_POST['ens_unit']) ? sanitize_text_field($_POST['ens_unit']) : '');
    update_post_meta($post_id,'_ens_specs',isset($_POST['ens_specs']) ? sanitize_textarea_field($_POST['ens_specs']) : '');
    $gallery=isset($_POST['ens_gallery']) ? implode(',',array_filter(array_map('absint',explode(',',sanitize_text_field($_POST['ens_gallery']))))) : '';
    update_post_meta($post_id,'_ens_gallery',$gallery);
}
add_action('save_post_ens_product','ens_save_product_meta');

function ens_feature_meta_box() { add_meta_box('ens_feature_details','Configuración del acceso','ens_feature_meta_html','ens_feature','normal','high'); }
add_action('add_meta_boxes','ens_feature_meta_box');
function ens_feature_meta_html($post) { wp_nonce_field('ens_feature_save','ens_feature_nonce'); $icon=get_post_meta($post->ID,'_ens_feature_icon',true); $url=get_post_meta($post->ID,'_ens_feature_url',true); echo '<p><label><strong>Ícono o símbolo corto</strong><br><input type="text" name="ens_feature_icon" value="'.esc_attr($icon).'" placeholder="⚒" style="width:180px"></label></p><p><label><strong>Enlace</strong><br><input type="url" name="ens_feature_url" value="'.esc_attr($url).'" placeholder="https://..." style="width:100%"></label></p><p class="description">El título del contenido es el nombre visible. El extracto es la descripción del acceso.</p>'; }
function ens_save_feature_meta($post_id) { if (!isset($_POST['ens_feature_nonce']) || !wp_verify_nonce($_POST['ens_feature_nonce'],'ens_feature_save')) return; if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; if (!current_user_can('edit_post',$post_id)) return; update_post_meta($post_id,'_ens_feature_icon',sanitize_text_field($_POST['ens_feature_icon'] ?? '')); update_post_meta($post_id,'_ens_feature_url',esc_url_raw($_POST['ens_feature_url'] ?? '')); }
add_action('save_post_ens_feature','ens_save_feature_meta');

function ens_admin_media_assets($hook) {
    $screen=get_current_screen(); $is_product=$screen && $screen->post_type==='ens_product' && in_array($hook,array('post.php','post-new.php'),true); $is_feature=$screen && $screen->post_type==='ens_feature' && in_array($hook,array('post.php','post-new.php'),true);
    if (!$is_product && !$is_feature) return;
    wp_enqueue_media();
    wp_add_inline_style('wp-admin','.ens-admin-product-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}.ens-gallery-preview{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}.ens-gallery-preview img{width:76px;height:76px;object-fit:cover;border:1px solid #dcdcde;border-radius:5px}@media(max-width:700px){.ens-admin-product-grid{grid-template-columns:1fr}}');
    wp_add_inline_script('jquery-core',"jQuery(function($){var frame;$('.ens-gallery-button').on('click',function(e){e.preventDefault();if(frame){frame.open();return}frame=wp.media({title:'Elegir imágenes del producto',button:{text:'Usar estas imágenes'},multiple:true});frame.on('select',function(){var ids=[];var html='';frame.state().get('selection').each(function(a){var o=a.toJSON();ids.push(o.id);html+='<img src=\"'+(o.sizes&&o.sizes.thumbnail?o.sizes.thumbnail.url:o.url)+'\" alt=\"\">';});$('#ens_gallery').val(ids.join(','));$('#ens_gallery_preview').html(html)});frame.open()})});");
}
add_action('admin_enqueue_scripts','ens_admin_media_assets');

function ens_admin_menu() { add_submenu_page('edit.php?post_type=ens_product','Carga rápida','Carga rápida','edit_posts','ens_quick_add','ens_quick_add_page'); }
add_action('admin_menu','ens_admin_menu');
function ens_quick_add_page() { if (!current_user_can('edit_posts')) return; $terms=get_terms(array('taxonomy'=>'ens_product_cat','hide_empty'=>false)); echo '<div class="wrap ens-quick-wrap"><h1>Cargar producto</h1><p class="description">Creá un producto en una sola pantalla. Después podés editar todos los detalles desde Productos.</p>'; if(isset($_GET['ens_created'])) echo '<div class="notice notice-success is-dismissible"><p>Producto creado correctamente.</p></div>'; echo '<div class="ens-quick-grid"><form method="post" id="ens-quick-form" class="ens-quick-card">'.wp_nonce_field('ens_quick_add_product','ens_quick_add_nonce',true,false).'<input type="hidden" name="ens_quick_add" value="1"><h2>Datos básicos</h2><label>Nombre del producto<input required type="text" name="ens_quick_title" placeholder="Ej: Maza para albañil 1000 g"></label><div class="ens-quick-two"><label>Precio<input required type="number" min="0" step="0.01" name="ens_quick_price" placeholder="30088"></label><label>SKU / código<input type="text" name="ens_quick_sku" placeholder="Opcional"></label></div><label>Categoría<select name="ens_quick_category"><option value="">Elegí una categoría</option>'; foreach($terms as $term) echo '<option value="'.esc_attr($term->term_id).'">'.esc_html($term->name).'</option>'; echo '</select></label><label>O crear categoría nueva<input type="text" name="ens_quick_new_category" placeholder="Ej: Herramientas eléctricas"></label><label>Descripción<textarea name="ens_quick_description" rows="6" placeholder="Describí el producto, materiales y usos"></textarea></label><label>Disponibilidad<select name="ens_quick_stock"><option>En stock</option><option>Consultar</option><option>Agotado</option></select></label><label class="ens-quick-check"><input type="checkbox" name="ens_quick_featured" value="1"> Producto destacado</label><button class="button button-primary button-hero" type="submit">Guardar producto</button></form><aside class="ens-quick-card"><h2>Imagen principal</h2><p>Elegí una imagen desde la Biblioteca de medios.</p><button class="button ens-quick-media">Elegir imagen</button><input type="hidden" name="ens_quick_image" id="ens_quick_image" form="ens-quick-form"><div class="ens-quick-preview" id="ens_quick_preview"><span>Sin imagen todavía</span></div><p class="description">Después de guardar podés agregar una galería desde Editar producto.</p><hr><h2>Categorías</h2><p class="description">Podés crear, editar o eliminar categorías desde Productos → Categorías.</p><a class="button" href="'.esc_url(admin_url('edit-tags.php?taxonomy=ens_product_cat&post_type=ens_product')).'">Administrar categorías</a><p style="margin-top:20px"><a class="button" href="'.esc_url(admin_url('edit.php?post_type=ens_product')).'">Ver todos los productos</a></p></aside></div></div>'; }
function ens_handle_quick_add() { if(!is_admin() || !isset($_POST['ens_quick_add'])) return; if(!current_user_can('edit_posts')) wp_die('No tenés permisos para crear productos.'); check_admin_referer('ens_quick_add_product','ens_quick_add_nonce'); $title=sanitize_text_field(wp_unslash($_POST['ens_quick_title']??'')); if(!$title) return; $desc=wp_kses_post(wp_unslash($_POST['ens_quick_description']??'')); $post_id=wp_insert_post(array('post_title'=>$title,'post_content'=>$desc,'post_excerpt'=>wp_trim_words(wp_strip_all_tags($desc),22),'post_status'=>'publish','post_type'=>'ens_product'),true); if(is_wp_error($post_id)) return; update_post_meta($post_id,'_ens_price',sanitize_text_field(wp_unslash($_POST['ens_quick_price']??''))); update_post_meta($post_id,'_ens_sku',sanitize_text_field(wp_unslash($_POST['ens_quick_sku']??''))); update_post_meta($post_id,'_ens_stock',sanitize_text_field(wp_unslash($_POST['ens_quick_stock']??'En stock'))); update_post_meta($post_id,'_ens_featured',isset($_POST['ens_quick_featured'])?'1':'0'); $new=sanitize_text_field(wp_unslash($_POST['ens_quick_new_category']??'')); if($new){$term=wp_insert_term($new,'ens_product_cat');if(!is_wp_error($term))wp_set_post_terms($post_id,array((int)$term['term_id']),'ens_product_cat');}elseif(!empty($_POST['ens_quick_category']))wp_set_post_terms($post_id,array((int)$_POST['ens_quick_category']),'ens_product_cat'); $img=absint($_POST['ens_quick_image']??0);if($img)set_post_thumbnail($post_id,$img); wp_safe_redirect(add_query_arg('ens_created','1',admin_url('edit.php?post_type=ens_product&page=ens_quick_add')));exit; }
add_action('admin_init','ens_handle_quick_add');
function ens_quick_add_assets($hook) { if($hook!=='ens_product_page_ens_quick_add')return; wp_enqueue_media(); wp_add_inline_style('wp-admin','.ens-quick-wrap{max-width:1100px}.ens-quick-grid{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(280px,.7fr);gap:20px;align-items:start}.ens-quick-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:24px;box-shadow:0 4px 16px rgba(16,24,40,.04)}.ens-quick-card h2{margin-top:0}.ens-quick-card label{display:block;font-weight:600;margin:16px 0 6px}.ens-quick-card input[type=text],.ens-quick-card input[type=number],.ens-quick-card textarea,.ens-quick-card select{width:100%;min-height:40px}.ens-quick-two{display:grid;grid-template-columns:1fr 1fr;gap:14px}.ens-quick-check{font-weight:400!important}.ens-quick-preview{min-height:180px;margin:16px 0;border:1px dashed #c3c4c7;border-radius:8px;display:grid;place-items:center;background:#f6f7f7;color:#646970;overflow:hidden}.ens-quick-preview img{width:100%;height:180px;object-fit:contain}@media(max-width:780px){.ens-quick-grid,.ens-quick-two{grid-template-columns:1fr}}'); wp_add_inline_script('jquery-core',"jQuery(function($){var f;$('.ens-quick-media').on('click',function(e){e.preventDefault();if(f){f.open();return}f=wp.media({title:'Elegir imagen del producto',button:{text:'Usar esta imagen'},multiple:false});f.on('select',function(){var a=f.state().get('selection').first().toJSON();$('#ens_quick_image').val(a.id);$('#ens_quick_preview').html('<img src=\"'+(a.sizes&&a.sizes.medium?a.sizes.medium.url:a.url)+'\" alt=\"\">')});f.open()})});"); }
add_action('admin_enqueue_scripts','ens_quick_add_assets');

function ens_product_columns($columns) { $new=array();foreach($columns as $key=>$label){$new[$key]=$label;if($key==='title'){$new['ens_price']='Precio';$new['ens_category']='Categoría';$new['ens_stock']='Disponibilidad';}}return $new; }
add_filter('manage_ens_product_posts_columns','ens_product_columns');
function ens_product_column_content($column,$post_id){if($column==='ens_price')echo esc_html(ens_price($post_id));if($column==='ens_category'){$terms=get_the_terms($post_id,'ens_product_cat');echo $terms&&!is_wp_error($terms)?esc_html(implode(', ',wp_list_pluck($terms,'name'))):'Sin categoría';}if($column==='ens_stock')echo esc_html(get_post_meta($post_id,'_ens_stock',true)?:'En stock');}
add_action('manage_ens_product_posts_custom_column','ens_product_column_content',10,2);

function ens_customizer($wp_customize) {
    $wp_customize->add_section('ens_brand',array('title'=>'El Nuevo Sol: marca y tienda','priority'=>30));
    $settings=array(
        'ens_brand_name'=>array('Nombre de marca','El Nuevo Sol'),
        'ens_brand_sub'=>array('Bajada','Ferretería'),
        'ens_hero_eyebrow'=>array('Texto superior de portada','Herramientas para hacer bien el trabajo'),
        'ens_hero_title'=>array('Título principal de portada','Todo lo que tu obra necesita.'),
        'ens_hero_text'=>array('Texto de portada','Soluciones confiables para profesionales, talleres y proyectos de todos los días.'),
        'ens_whatsapp'=>array('WhatsApp para recibir pedidos','5491100000000'),
        'ens_contact_email'=>array('Email de contacto','hola@elnuevosol.com'),
        'ens_contact_hours'=>array('Horarios','Lunes a viernes, 8 a 18 h')
    );
    foreach($settings as $id=>$data){$wp_customize->add_setting($id,array('default'=>$data[1],'sanitize_callback'=>'sanitize_text_field'));$wp_customize->add_control($id,array('label'=>$data[0],'section'=>'ens_brand','type'=>'text'));}
    $wp_customize->add_setting('ens_logo',array('default'=>'','sanitize_callback'=>'esc_url_raw'));$wp_customize->add_control(new WP_Customize_Image_Control($wp_customize,'ens_logo',array('label'=>'Logo editable','section'=>'ens_brand')));
    $wp_customize->add_setting('ens_hero_image',array('default'=>'','sanitize_callback'=>'esc_url_raw'));$wp_customize->add_control(new WP_Customize_Image_Control($wp_customize,'ens_hero_image',array('label'=>'Imagen del banner principal','section'=>'ens_brand')));
    $wp_customize->add_setting('ens_hero_image_mobile',array('default'=>'','sanitize_callback'=>'esc_url_raw'));$wp_customize->add_control(new WP_Customize_Image_Control($wp_customize,'ens_hero_image_mobile',array('label'=>'Imagen del banner en celular','section'=>'ens_brand')));
    $wp_customize->add_setting('ens_primary_color',array('default'=>'#161f55','sanitize_callback'=>'sanitize_hex_color'));$wp_customize->add_control(new WP_Customize_Color_Control($wp_customize,'ens_primary_color',array('label'=>'Azul principal','section'=>'ens_brand')));
    $wp_customize->add_setting('ens_accent_color',array('default'=>'#f0783c','sanitize_callback'=>'sanitize_hex_color'));$wp_customize->add_control(new WP_Customize_Color_Control($wp_customize,'ens_accent_color',array('label'=>'Naranja de acento','section'=>'ens_brand')));
    $wp_customize->add_setting('ens_yellow_color',array('default'=>'#f7c948','sanitize_callback'=>'sanitize_hex_color'));$wp_customize->add_control(new WP_Customize_Color_Control($wp_customize,'ens_yellow_color',array('label'=>'Amarillo secundario','section'=>'ens_brand')));
}
add_action('customize_register','ens_customizer');
function ens_customizer_css(){echo '<style>:root{--ens-navy:'.esc_attr(get_theme_mod('ens_primary_color','#161f55')).';--ens-orange:'.esc_attr(get_theme_mod('ens_accent_color','#f0783c')).';--ens-yellow:'.esc_attr(get_theme_mod('ens_yellow_color','#f7c948')).'}</style>';}
add_action('wp_head','ens_customizer_css');

function ens_logo_html(){
    $logo=get_theme_mod('ens_logo','');
    if($logo) return '<img class="ens-brand-image" src="'.esc_url($logo).'" alt="'.esc_attr(get_theme_mod('ens_brand_name','El Nuevo Sol')).'">';
    return '';
}
function ens_header(){
    $cart=get_permalink(get_page_by_path('carrito')) ?: home_url('/carrito/');
    echo '<div class="ens-topline"></div><header class="ens-header"><div class="ens-container ens-header-inner">';
    $logo=ens_logo_html(); $logo_wrap=$logo ? '<span class="ens-brand-mark">'.$logo.'</span>' : ''; echo '<a class="ens-brand" href="'.esc_url(home_url('/')).'">'.$logo_wrap.'<span><span class="ens-brand-name">'.esc_html(get_theme_mod('ens_brand_name','El Nuevo Sol')).'</span><span class="ens-brand-sub">'.esc_html(get_theme_mod('ens_brand_sub','Ferretería')).'</span></span></a>';
    echo '<form class="ens-search" role="search" action="'.esc_url(home_url('/')).'"><select aria-label="Buscar en"><option>Todo el sitio</option><option>Herramientas</option><option>Construcción</option></select><input name="s" placeholder="¿Qué herramienta estás buscando?" value="'.esc_attr(get_search_query()).'"><button aria-label="Buscar">⌕</button></form>';
    echo '<div class="ens-actions"><a class="ens-icon-btn" href="'.esc_url(get_permalink(get_page_by_path('contacto')) ?: home_url('/contacto/')).'" aria-label="Información">i</a><a class="ens-icon-btn ens-cart-icon" href="'.esc_url($cart).'" aria-label="Carrito">🛒<span class="ens-cart-count" data-ens-cart-count>0</span></a></div>';
    echo '</div></header><nav class="ens-nav"><div class="ens-container ens-nav-inner">';
    if(has_nav_menu('primary')) wp_nav_menu(array('theme_location'=>'primary','container'=>false,'fallback_cb'=>false,'items_wrap'=>'%3$s')); else {echo '<a href="'.esc_url(home_url('/')).'">Inicio</a><a href="'.esc_url(get_post_type_archive_link('ens_product')).'">Herramientas</a><a href="'.esc_url(get_permalink(get_page_by_path('nosotros')) ?: '#').'">La ferretería</a><a href="'.esc_url(get_permalink(get_page_by_path('contacto')) ?: '#').'">Ayuda</a>';} echo '</div></nav>';
}
function ens_footer(){echo '<footer class="ens-footer"><div class="ens-container ens-footer-inner"><div><span class="ens-accent">'.esc_html(get_theme_mod('ens_brand_name','El Nuevo Sol')).'</span> '.esc_html(get_theme_mod('ens_brand_sub','Ferretería')).', herramientas para todos los días.</div><small>Compra segura · Atención cercana · Pedidos por WhatsApp</small></div></footer>';}
function ens_breadcrumb($current='Inicio'){echo '<div class="ens-container ens-crumbs"><a href="'.esc_url(home_url('/')).'">Inicio</a> <span>/</span> <strong>'.esc_html($current).'</strong></div>';}

function ens_price($id){$p=get_post_meta($id,'_ens_price',true);return $p!=='' ? '$ '.number_format((float)$p,2,',','.') : 'Consultar';}
function ens_product_card($post_id){$cat=get_the_terms($post_id,'ens_product_cat');$cat_name=($cat && !is_wp_error($cat))?$cat[0]->name:'Herramientas';$featured=get_post_meta($post_id,'_ens_featured',true)==='1';echo '<article class="ens-card"><div class="ens-card-img">'.($featured?'<span class="ens-tag">Destacado</span>':'');if(has_post_thumbnail($post_id)) echo get_the_post_thumbnail($post_id,'medium'); else echo '<svg class="ens-card-tool" viewBox="0 0 240 180"><path d="M120 20C115 75 112 110 116 150" stroke="#b97745" stroke-width="12" stroke-linecap="round"/><path d="M70 56Q120 25 170 56L162 70Q120 55 78 70Z" fill="#f0783c" stroke="#162044" stroke-width="8"/></svg>';echo '</div><div class="ens-card-body"><div class="ens-card-cat">'.esc_html($cat_name).'</div><h2>'.esc_html(get_the_title($post_id)).'</h2><p>'.esc_html(get_the_excerpt($post_id)).'</p><div class="ens-card-foot"><div class="ens-price"><small>Precio final</small>'.esc_html(ens_price($post_id)).'</div><button class="ens-buy" data-ens-add="'.esc_attr($post_id).'">🛒 Agregar</button></div></div></article>';}

function ens_create_default_features(){
    if (get_posts(array('post_type'=>'ens_feature','posts_per_page'=>1,'post_status'=>'any'))) return;
    $defaults=array(
        array('Herramientas','Para cada tarea y oficio','⚒'),
        array('Construcción','Obra, reparación y mantenimiento','▣'),
        array('Seguridad','Protección para trabajar tranquilo','◉'),
        array('Te asesoramos','Encontrá la opción correcta','?')
    );
    foreach($defaults as $feature){$id=wp_insert_post(array('post_type'=>'ens_feature','post_title'=>$feature[0],'post_excerpt'=>$feature[1],'post_status'=>'publish','menu_order'=>count($defaults)));if($id){update_post_meta($id,'_ens_feature_icon',$feature[2]);update_post_meta($id,'_ens_feature_url',get_post_type_archive_link('ens_product'));}}
}
add_action('after_switch_theme','ens_create_default_features');

function ens_create_pages(){
    $pages=array('Inicio'=>'front-page.php','Catálogo'=>'','Carrito'=>'page-carrito.php','La ferretería'=>'page-nosotros.php','Contacto'=>'page-contacto.php');
    foreach($pages as $title=>$template){$slug=sanitize_title($title);$existing=get_page_by_path($slug);if(!$existing){$id=wp_insert_post(array('post_title'=>$title,'post_name'=>$slug,'post_status'=>'publish','post_type'=>'page'));if($id && $template) update_post_meta($id,'_wp_page_template',$template);}}
    if(!get_option('show_on_front')){update_option('show_on_front','page');$home=get_page_by_path('inicio');if($home) update_option('page_on_front',$home->ID);}
}
add_action('after_switch_theme','ens_create_pages');

function ens_body_class($classes){$classes[]='ens-theme';return $classes;}add_filter('body_class','ens_body_class');
