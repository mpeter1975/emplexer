<?php
/**
 * @author Erik Clemente (aka newloran2)
 */

/**
* Singleton to manage emplexer templates
*/
class TemplateManager
{

    private $reload =true; //if true reload the template on every call
    private $templateFile ;
    private $templateJson;
    private static $instance;

    private function __construct()
    {
        $this->templateFile = ROOT_PATH . "/templates/default.json" ;
    }

    protected function reloadTemplate(){
        $this->templateJson = json_decode(file_get_contents($this->templateFile));
    }

    public static function getInstance(){
        if (!isset(self::$instance)){
            self::$instance =  new TemplateManager();
        }
        if (self::$instance->reload) {
            self::$instance->reloadTemplate();
        }

        return self::$instance;
    }


    private function getTag($template, $tag, $getFieldCallBack, $item = null){
        if (!isset($this->templateJson->{$template})){
            $template = "base";
        }
        $temp = $this->templateJson->{$template};
        $ret =  array();
        $unset = isset($temp->{$tag}->unset) && $temp->{$tag}->unset == true ;
        if (isset($temp->inherits) && !$unset){
            $ret = $this->getTag($temp->inherits, $tag, $getFieldCallBack , $item);
        }
        if (isset($temp->{$tag})){
            foreach ($temp->{$tag} as $key => $value) {
                if ($key  !== "unset"){
                    $ret[$key]  = call_user_func_array($getFieldCallBack,array($value, $item));
                }
            }
        }
        return $ret;


    }

    public function getTemplate($name, $getMediaUrlCallback, $getDataCallback, $getFieldCallBack){
        $itens = array();
        $folderItems = array();
        $data = call_user_func($getDataCallback);
        foreach ( $data as $item)
        {
            $viewItemParams = array();
            if (isset($this->templateJson->base->items->view_item_params)){
                foreach ($this->templateJson->base->items->view_item_params as $key => $value) {
                    $viewItemParams[$key] =  call_user_func_array($getFieldCallBack,array($value, $item));
                }
            }
            $folderItems[] = array(
                PluginRegularFolderItem::media_url          => call_user_func($getMediaUrlCallback, $item),
                PluginRegularFolderItem::caption            => call_user_func_array($getFieldCallBack,array($this->templateJson->base->items->caption, $item)) ,
                PluginRegularFolderItem::view_item_params  => $viewItemParams
            );
        }

        $availableTemplates = array(
            PluginRegularFolderView::async_icon_loading             => true,
            PluginRegularFolderView::initial_range                  =>
            array(
                PluginRegularFolderRange::items                         =>  $folderItems,
                PluginRegularFolderRange::total                         =>  count($folderItems),
                PluginRegularFolderRange::count                         =>  count($folderItems),
                PluginRegularFolderRange::more_items_available          =>  false,
                PluginRegularFolderRange::from_ndx                      =>  0
                ),
            PluginRegularFolderView::view_params                    => $this->getTag($name, "view_params",  $getFieldCallBack),
            PluginRegularFolderView::base_view_item_params          => $this->getTag($name, "base_view_item_params",  $getFieldCallBack),
            PluginRegularFolderView::not_loaded_view_item_params    => $this->getTag($name, "not_loaded_view_item_params",  $getFieldCallBack),
        );

        $a = array(
            PluginFolderView::view_kind                             =>  PLUGIN_FOLDER_VIEW_REGULAR,
            PluginFolderView::data                                  => $availableTemplates
        );
        return $a;

    }

}

?>