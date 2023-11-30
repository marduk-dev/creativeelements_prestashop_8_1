<?php
/**
 * Fix for CreativeElements in PrestaShop 8.1
 *
 * @author Mateusz Bednarski <mateusz@marduk.dev>
 *
 */

defined('_PS_VERSION_') or exit;

class CreativeElementsOverride extends CreativeElements 
{

    public function hookOverrideLayoutTemplate($params = [])
    {
        // fix for PrestaShop 8.1 - this call is just to get layout name, page is not yet created
        if (!self::isPageInitiated()) {
            return null;
        }
        if (null !== self::$tplOverride || !self::$controller) {
            return self::$tplOverride;
        }
        self::$tplOverride = '';

        if (self::isMaintenance()) {
            return self::$tplOverride;
        }
        // Page Content
        $controller = self::$controller;
        $tpl_vars = &$this->context->smarty->tpl_vars;
        $front = strtolower(preg_replace('/(ModuleFront)?Controller(Override)?$/i', '', get_class($controller)));
        // PrestaBlog fix for non-default blog URL
        strpos($front, 'prestablog') === 0 && property_exists($controller, 'news') && $front = 'prestablogblog';

        switch ($front) {
            case 'creativeelementspreview':
                $model = self::getPreviewUId(false)->getModel();
                $key = $model::${'definition'}['table'];

                if (isset($tpl_vars[$key]->value['id'])) {
                    $id = $tpl_vars[$key]->value['id'];
                    $desc = ['description' => &$tpl_vars[$key]->value['content']];
                }
                break;
            case 'cms':
                $model = class_exists('CMS') ? 'CMS' : 'CMSCategory';
                $key = $model::${'definition'}['table'];

                if (isset($tpl_vars[$key]->value['id'])) {
                    $id = $tpl_vars[$key]->value['id'];
                    $desc = ['description' => &$tpl_vars[$key]->value['content']];

                    CE\add_action('wp_head', 'print_og_image');
                } elseif (isset($tpl_vars['cms_category']->value['id'])) {
                    $model = 'CMSCategory';
                    $id = $tpl_vars['cms_category']->value['id'];
                    $desc = &$tpl_vars['cms_category']->value;
                }
                break;
            case 'product':
            case 'category':
            case 'manufacturer':
            case 'supplier':
                $model = $front;

                if (isset($tpl_vars[$model]->value['id'])) {
                    $id = $tpl_vars[$model]->value['id'];
                    $desc = &$tpl_vars[$model]->value;
                }
                break;
            case 'ybc_blogblog':
                $model = 'Ybc_blog_post_class';

                if (isset($tpl_vars['blog_post']->value['id_post'])) {
                    $id = $tpl_vars['blog_post']->value['id_post'];
                    $desc = &$tpl_vars['blog_post']->value;

                    if (Tools::getIsset('adtoken') && self::hasAdminToken('AdminModules')) {
                        // override post status for preview
                        $tpl_vars['blog_post']->value['enabled'] = 1;
                    }
                }
                break;
            case 'xipblogsingle':
                $model = 'XipPostsClass';

                if (isset($tpl_vars['xipblogpost']->value['id_xipposts'])) {
                    $id = $tpl_vars['xipblogpost']->value['id_xipposts'];
                    $desc = ['description' => &$tpl_vars['xipblogpost']->value['post_content']];
                }
                break;
            case 'stblogarticle':
                $model = 'StBlogClass';

                if (isset($tpl_vars['blog']->value['id'])) {
                    $id = $tpl_vars['blog']->value['id'];
                    $desc = ['description' => &$tpl_vars['blog']->value['content']];
                    break;
                }
                $blogProp = new ReflectionProperty($controller, 'blog');
                $blogProp->setAccessible(true);
                $blog = $blogProp->getValue($controller);

                if (isset($blog->id)) {
                    $id = $blog->id;
                    $desc = ['description' => &$blog->content];
                }
                break;
            case 'advanceblogdetail':
                $model = 'BlogPosts';

                if (isset($tpl_vars['postData']->value['id_post'])) {
                    $id = $tpl_vars['postData']->value['id_post'];
                    $desc = ['description' => &$tpl_vars['postData']->value['post_content']];
                }
                break;
            case 'prestablogblog':
                $model = 'NewsClass';
                $newsProp = new ReflectionProperty($controller, 'news');
                $newsProp->setAccessible(true);
                $news = $newsProp->getValue($controller);

                if (isset($news->id)) {
                    $id = $news->id;

                    if (isset($tpl_vars['tpl_unique'])) {
                        $desc = ['description' => &$tpl_vars['tpl_unique']->value];
                    } else {
                        $desc = ['description' => &$news->content];
                    }
                }
                break;
            case 'hiblogpostdetails':
                $model = 'HiBlogPost';

                if (isset($tpl_vars['post']->value['id_post'])) {
                    $id = $tpl_vars['post']->value['id_post'];
                    $desc = &$tpl_vars['post']->value;

                    if (Tools::getIsset('adtoken') && self::hasAdminToken('AdminModules')) {
                        // override post status for preview
                        $tpl_vars['post']->value['enabled'] = 1;
                    }
                }
                break;
            case 'tvcmsblogsingle':
                $model = 'TvcmsPostsClass';

                if (isset($tpl_vars['tvcmsblogpost']->value['id_tvcmsposts'])) {
                    $id = $tpl_vars['tvcmsblogpost']->value['id_tvcmsposts'];
                    $desc = ['description' => &$tpl_vars['tvcmsblogpost']->value['post_content']];
                }
                break;
            case 'pm_advancedsearch4searchresults':
                $model = 'category';

                if (isset($tpl_vars[$model]->value['id'])) {
                    $id = $tpl_vars[$model]->value['id'];
                    $desc = &$tpl_vars[$model]->value;
                }
                break;
        }

        if (isset($id)) {
            $uid_preview = self::getPreviewUId();

            if ($uid_preview && $uid_preview->id === (int) $id && $uid_preview->id_type === CE\UId::getTypeId($model)) {
                CE\UId::$_ID = $uid_preview;
            } elseif (!CE\UId::$_ID || in_array(CE\UId::$_ID->id_type, [CE\UId::CONTENT, CE\UId::THEME, CE\UId::TEMPLATE])) {
                CE\UId::$_ID = new CE\UId($id, CE\UId::getTypeId($model), $this->context->language->id, $this->context->shop->id);
            }

            if (CE\UId::$_ID) {
                $this->addBodyClasses('elementor-page', CE\UId::$_ID->toDefault());

                $desc['description'] = CE\apply_filters('the_content', $desc['description']);
            }
        }

        // Theme Builder
        $themes = [
            'header' => Configuration::get('CE_HEADER'),
            'footer' => Configuration::get('CE_FOOTER'),
        ];
        $pages = [
            'index' => 'page-index',
            'contact' => 'page-contact',
            'product' => 'product',
            'pagenotfound' => 'page-not-found',
        ];
        foreach ($pages as $page_type => $theme_type) {
            if ($front === $page_type) {
                $themes[$theme_type] = Configuration::get(self::getThemeVarName($theme_type));
                break;
            }
        }
        $uid = CE\UId::$_ID;
        $uid_preview = self::getPreviewUId(false);

        if ($uid_preview && (CE\UId::THEME === $uid_preview->id_type || CE\UId::TEMPLATE === $uid_preview->id_type)) {
            $preview = self::renderTheme($uid_preview);
            $document = CE\Plugin::instance()->documents->getDocForFrontend($uid_preview);
            $type_preview = $document->getTemplateType();
            $this->context->smarty->assign(self::getThemeVarName($type_preview), $preview);

            if ('product-quick-view' === $type_preview) {
                unset($desc);
                $desc = ['description' => &$preview];
                CE\Plugin::instance()->modules_manager->getModules('catalog')->handleProductQuickView();

                $this->context->smarty->assign('CE_PRODUCT_QUICK_VIEW_ID', $uid_preview->id);
            } elseif ('product-miniature' === $type_preview) {
                unset($desc);
                $desc = ['description' => &$preview];
                CE\Plugin::instance()->modules_manager->getModules('catalog')->handleProductMiniature();

                $this->context->smarty->assign('CE_PRODUCT_MINIATURE_ID', $uid_preview->id);
            } elseif ('product' === $type_preview) {
                $this->context->smarty->assign('CE_PRODUCT_ID', $uid_preview->id);
            } elseif (strpos($type_preview, 'page-') === 0) {
                $desc = ['description' => &$preview];
                CE\add_action('wp_head', 'print_og_image');
            }
            array_search($type_preview, $pages) && $this->addBodyClasses('ce-theme', $uid_preview->id);
            unset($themes[$type_preview]);
        }
        if (isset($pages[$front]) && !empty($themes[$pages[$front]])) {
            $theme_type = $pages[$front];
            $uid_theme = new CE\UId($themes[$theme_type], CE\UId::THEME, $this->context->language->id, $this->context->shop->id);

            if ('product' === $page_type) {
                $this->context->smarty->assign([
                    'CE_PRODUCT_ID' => $uid_theme->id,
                    'CE_PRODUCT' => self::renderTheme($uid_theme),
                ]);
            } else {
                $desc = ['description' => self::renderTheme($uid_theme)];
                $this->context->smarty->assign(self::getThemeVarName($theme_type), $desc['description']);
                CE\add_action('wp_head', 'print_og_image');
            }
            $this->addBodyClasses('ce-theme', $uid_theme->id);
            unset($themes[$theme_type]);
        }

        self::$tplOverride = CE\apply_filters('template_include', self::$tplOverride);

        if (strrpos(self::$tplOverride, 'layout-canvas') !== false) {
            empty($desc) or $this->context->smarty->assign('ce_desc', $desc);
        } else {
            foreach ($themes as $theme_type => $id_ce_theme) {
                empty($id_ce_theme) or $this->context->smarty->assign(
                    self::getThemeVarName($theme_type),
                    self::renderTheme(
                        new CE\UId($id_ce_theme, CE\UId::THEME, $this->context->language->id, $this->context->shop->id)
                    )
                );
            }
        }
        CE\UId::$_ID = $uid;

        return self::$tplOverride;
    }

    private function isPageInitiated()
    {
        return array_key_exists('page', $this->context->smarty->tpl_vars);
    }
}

