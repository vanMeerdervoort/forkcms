<?php

namespace Backend\Modules\Search\Actions;

use Backend\Core\Engine\Base\Action;
use Backend\Core\Engine\DataGridDatabase as BackendDataGridDatabase;
use Backend\Core\Engine\DataGridFunctions as BackendDataGridFunctions;
use App\Component\Locale\BackendLanguage;
use Backend\Modules\Search\Engine\Model as BackendSearchModel;

/**
 * This is the statistics-action, it will display the overview of search statistics
 */
class Statistics extends Action
{
    public function execute(): void
    {
        parent::execute();
        $this->showDataGrid();
        $this->display();
    }

    private function showDataGrid(): void
    {
        $dataGrid = new BackendDataGridDatabase(
            BackendSearchModel::QUERY_DATAGRID_BROWSE_STATISTICS,
            [BackendLanguage::getWorkingLanguage()]
        );
        $dataGrid->setColumnsHidden(['data']);
        $dataGrid->addColumn('referrer', BackendLanguage::lbl('Referrer'));
        $dataGrid->setHeaderLabels(['time' => \SpoonFilter::ucfirst(BackendLanguage::lbl('SearchedOn'))]);

        // set column function
        $dataGrid->setColumnFunction([__CLASS__, 'parseRefererInDataGrid'], '[data]', 'referrer');
        $dataGrid->setColumnFunction(
            [new BackendDataGridFunctions(), 'getLongDate'],
            ['[time]'],
            'time',
            true
        );
        $dataGrid->setColumnFunction('htmlspecialchars', ['[term]'], 'term');

        $dataGrid->setSortingColumns(['time', 'term'], 'time');
        $dataGrid->setSortParameter('desc');

        $this->template->assign('dataGrid', $dataGrid->getContent());
    }

    public static function parseRefererInDataGrid(string $data): string
    {
        $data = unserialize($data);
        if (!isset($data['server']['HTTP_REFERER'])) {
            return '';
        }

        $referrer = htmlspecialchars($data['server']['HTTP_REFERER']);

        return '<a href="' . $referrer . '">' . $referrer . '</a>';
    }
}
