<?php
namespace Dots\Block\Handler;
use Zend\EventManager\EventCollection,
    Zend\EventManager\Event,
    Zend\EventManager\ListenerAggregate,

    Dots\Module,
    Dots\Db\Entity\Block,
    Dots\Db\Entity\ImageBlock,
    Dots\Form\MultiForm,
    Dots\Form\Block\ImageContentForm,
    Dots\Block\ContentHandler,
    Dots\Block\HandlerAware,
    Dots\Thumbs\PhpThumbFactory;

class ImageContent implements HandlerAware
{
    const TYPE = 'image_content';
    protected $listeners = array();
    protected $handler = null;

    /**
     * Attach events to the application and listen for the dispatch event
     * @param \Zend\EventManager\EventCollection $events
     * @return void
     */
    public function attach(EventCollection $events, $priority = null)
    {
        $this->listeners[] = $events->attach('listHandlers', array($this, 'getHandler'), $priority);
        $this->listeners[] = $events->attach('renderBlock/' . static::TYPE, array($this, 'renderBlock'), $priority);
        $this->listeners[] = $events->attach('editBlock/' . static::TYPE, array($this, 'editBlock'), $priority);
        $this->listeners[] = $events->attach('saveBlock/' . static::TYPE, array($this, 'saveBlock'), $priority);
        $this->listeners[] = $events->attach('removeBlock/' . static::TYPE, array($this, 'removeBlock'), $priority);
    }

    /**
     * Detach all the event listeners from the event collection
     * @param \Zend\EventManager\EventCollection $events
     * @return void
     */
    public function detach(EventCollection $events)
    {
        foreach ($this->listeners as $key => $listener) {
            $events->detach($listener);
            unset($this->listeners[$key]);
            unset($listener);
        }
    }

    /**
     * Get Content Handler
     * @return ContentHandler
     */
    public function getHandler()
    {
        if (!$this->handler){
            $this->handler = new ContentHandler(static::TYPE, 'Image Content');
        }
        return $this->handler;
    }

    public function renderBlock(Event $event)
    {
        $locator = Module::locator();
        $view = $locator->get('view');
        $block = $event->getTarget();
        $page = $event->getParam('page');
        $locator = Module::locator();
        $model = $locator->get('Dots\Db\Model\ImageBlock');
        $imageBlock = $model->getByBlockId($block->id);
        return $view->render('dots/blocks/image/render', array(
            'page' => $page,
            'block' => $block,
            'imageBlock' => $imageBlock,
        ));
    }

    public function editBlock(Event $event)
    {
        $locator = Module::locator();
        $view = $locator->get('view');
        $block = $event->getTarget();
        $page = $event->getParam('page');
        $section = $event->getParam('section');
        if ($block){
            $model = $locator->get('Dots\Db\Model\ImageBlock');
            $imageBlock = $model->getByBlockId($block->id);
        }else{
            $block = new Block();
            $block->type = static::TYPE;
            $block->section = $section;
            $imageBlock = new ImageBlock();
        }
        $form = new MultiForm(array(
            'image_content' => new ImageContentForm($imageBlock)
        ));
        $form->setView($view);
        $form->setDecorators(array(
            array('ViewScript', array('viewScript' => 'dots/blocks/image/edit-form'))
        ));
        $form->addButtons();
        $form->populate(array('image_content'=>$imageBlock->toArray()));
        return $view->render('dots/blocks/image/edit', array(
            'page' => $page,
            'block' => $block,
            'imageBlock' => $imageBlock,
            'form' => $form,
        ));
    }

    public function saveBlock(Event $event)
    {
        $locator = Module::locator();
        $modelBlock = $locator->get('Dots\Db\Model\Block');
        $modelImageBlock = $locator->get('Dots\Db\Model\ImageBlock');
        $view = $locator->get('view');
        $block = $event->getTarget();
        $page = $event->getParam('page');
        $section = $event->getParam('section');
        $position = $event->getParam('position', 1);
        $form = new MultiForm(array(
            'image_content' => new ImageContentForm()
        ));

        if ($form->isValid($_POST)){
            $data = $form->getValues();
            if ($block) {
                $block->position = $position;
                $imageBlock = $modelImageBlock->getByBlockId($block->id);
            } else {
                $block = new Block();
                $block->position = $position;
                $block->type = static::TYPE;
                $block->section = $section;
                $block->page_id = $page->id;
                $block->save();
                $imageBlock = new ImageBlock();
                $imageBlock->block_id = $block->id;
            }

            $imageBlock->alt = $data['image_content']['alt'];
            $imageBlock->display_width = $data['image_content']['display_width'];
            $imageBlock->display_height = $data['image_content']['display_height'];
            $editedCrop = (
                $imageBlock->crop_x1 != $data['image_content']['crop_x1']
                || $imageBlock->crop_y1 != $data['image_content']['crop_y1']
                || $imageBlock->crop_x2 != $data['image_content']['crop_x2']
                || $imageBlock->crop_y2 != $data['image_content']['crop_y2']
            );
            $imageBlock->crop_x1 = $data['image_content']['crop_x1'];
            $imageBlock->crop_y1 = $data['image_content']['crop_y1'];
            $imageBlock->crop_x2 = $data['image_content']['crop_x2'];
            $imageBlock->crop_y2 = $data['image_content']['crop_y2'];

            if ( !($imageBlock->id && empty($data['image_content']['original_src'])) ){
                // success - do something with the uploaded file
                $fullFilePath = $data['image_content']['original_src'];
                if ($imageBlock->original_src){
                    unlink(PUBLIC_PATH.$imageBlock->original_src);
                }
                if ($imageBlock->src != $imageBlock->original_src) {
                    unlink(PUBLIC_PATH . $imageBlock->src);
                }
                $imageBlock->original_src = $fullFilePath;
                $imageBlock->src = $fullFilePath;
                $thumb = PhpThumbFactory::create(PUBLIC_PATH . $imageBlock->original_src);
                $dimensions = $thumb->getCurrentDimensions();
                $imageBlock->width = $dimensions['width'];
                $imageBlock->height = $dimensions['height'];
            }

            if ($editedCrop){
                if ($imageBlock->src != $imageBlock->original_src) {
                    unlink(PUBLIC_PATH . $imageBlock->src);
                }
                if ($imageBlock->crop_x1 !== "" && $imageBlock->crop_y1 !== "" && $imageBlock->crop_x2 !== "" && $imageBlock->crop_y2 !== "") {
                    $thumb = PhpThumbFactory::create(PUBLIC_PATH . $imageBlock->original_src);
                    if ($imageBlock->width && $imageBlock->height){
                        $w = $imageBlock->width;
                        $h = $imageBlock->height;
                    }else{
                        $dimensions = $thumb->getCurrentDimensions();
                        $imageBlock->width = $w = $dimensions['width'];
                        $imageBlock->height = $h = $dimensions['height'];
                    }

                    $x1 = round($imageBlock->crop_x1 * $w / 100);
                    $y1 = round($imageBlock->crop_y1 * $h / 100);
                    $x2 = round($imageBlock->crop_x2 * $w / 100);
                    $y2 = round($imageBlock->crop_y2 * $h / 100);
                    $thumb->crop($x1, $y1, $x2 - $x1, $y2 - $y1);
                    $filename = basename($imageBlock->original_src);
                    $filename = substr($filename, 0, strrpos($filename, '.')) . '.jpg';
                    $filename = '/data/uploads/edited/' . uniqid(rand()) . '_' . $filename;
                    $thumb->save(PUBLIC_PATH . $filename, 'jpg');
                    $imageBlock->src = $filename;
                } else {
                    $imageBlock->src = $imageBlock->original_src;
                }
            }

            $imageBlock->save();
            return $block;
        }
        $event->stopPropagation();
        $errors = $form->getMessages();
        return $errors;
    }

    public function removeBlock(Event $event)
    {
        $locator = Module::locator();
        $modelImageBlock = $locator->get('Dots\Db\Model\ImageBlock');
        $block = $event->getTarget();
        $imageBlock = $modelImageBlock->getByBlockId($block->id);
        if ($imageBlock->original_src){
            unlink(PUBLIC_PATH.$imageBlock->original_src);
        }
        if ($imageBlock->src) {
            unlink(PUBLIC_PATH . $imageBlock->src);
        }
        $imageBlock->delete();
        $block->delete();
        return true;
    }

    /**
     * Update object with received data
     * @param $obj
     * @param $data
     * @return mixed
     */
    private function updateObject($obj, $data)
    {
        foreach ($data as $key => $value) {
            $obj->$key = $value;
        }
        return $obj;
    }

}