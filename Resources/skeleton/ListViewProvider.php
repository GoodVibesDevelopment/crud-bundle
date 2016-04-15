<?php

namespace ##NAMESPACE##;

use Vardius\Bundle\ListBundle\ListView\Provider\ListViewProvider;
use ##FFCQN##;

class ##CLASS## extends ListViewProvider
{
    /**
     * {@inheritdoc}
     */
    public function buildListView()
    {
        $listView = $this->listViewFactory->get();

        $listView
            ->addColumn('id', 'property')
            ##BUILD_CODE##
            ->addFilter(##FCLASS##::class, 'provider.##TYPE_NAME##_filter')
        ;

        return $listView;
    }
}
