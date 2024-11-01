<?php

namespace App\Modules\tao\A0\cms\Helper;

use App\Modules\tao\A0\cms\Services\CmsAdService;
use App\Modules\tao\A0\cms\Services\CmsCategoryService;
use App\Modules\tao\A0\cms\Services\CmsContentService;
use App\Modules\tao\A0\cms\Services\CmsPageService;
use App\Modules\tao\Helper\MyMvcHelper;

class MyCmsMvcHelper
{
    public function __construct(public MyMvcHelper $mvc)
    {
        $this->injectServices();;
    }

    protected function injectServices(): void
    {
        $cms = $this;
        $this->mvc->di->setShared('tao.cms.category', function () use ($cms) {
            return new CmsCategoryService($cms);
        });
        $this->mvc->di->setShared('tao.cms.content', function () use ($cms) {
            return new CmsContentService($cms);
        });
        $this->mvc->di->setShared('tao.cms.page', function () use ($cms) {
            return new CmsPageService($cms);
        });
        $this->mvc->di->setShared('tao.cms.ad', function () use ($cms) {
            return new CmsAdService($cms);
        });
    }

    public function categoryService(): CmsCategoryService
    {
        return $this->mvc->di->get('tao.cms.category');
    }

    public function contentService(): CmsContentService
    {
        return $this->mvc->di->get('tao.cms.content');
    }

    public function pageService(): CmsPageService
    {
        return $this->mvc->di->get('tao.cms.page');
    }

    public function adService(): CmsAdService
    {
        return $this->mvc->di->get('tao.cms.ad');
    }


}