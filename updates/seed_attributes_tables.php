<?php namespace Ahoy\Pyrolancer\Updates;

use October\Rain\Database\Updates\Seeder;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\ProjectBid;
use Ahoy\Pyrolancer\Models\Attribute;

class SeedAttributesTables extends Seeder
{

    public function run()
    {
        $projectStatus = [
            ['name' => 'Draft', 'code' => ProjectModel::STATUS_DRAFT],
            ['name' => 'Pending', 'code' => ProjectModel::STATUS_PENDING],
            ['name' => 'Rejected', 'code' => ProjectModel::STATUS_REJECTED],
            ['name' => 'Active', 'code' => ProjectModel::STATUS_ACTIVE],
            ['name' => 'Suspended', 'code' => ProjectModel::STATUS_SUSPENDED],
            ['name' => 'Expired', 'code' => ProjectModel::STATUS_EXPIRED],
            ['name' => 'Cancelled', 'code' => ProjectModel::STATUS_CANCELLED],
            ['name' => 'Wait', 'code' => ProjectModel::STATUS_WAIT],
            ['name' => 'Declined', 'code' => ProjectModel::STATUS_DECLINED],
            ['name' => 'Development', 'code' => ProjectModel::STATUS_DEVELOPMENT],
            ['name' => 'Terminated', 'code' => ProjectModel::STATUS_TERMINATED],
            ['name' => 'Completed', 'code' => ProjectModel::STATUS_COMPLETED],
            ['name' => 'Closed', 'code' => ProjectModel::STATUS_CLOSED],
        ];

        $projectTypes = [
            ['name' => 'Bid request', 'label' => 'Give an estimate on how much it will cost', 'code' => 'auction'],
            ['name' => 'Position vacant', 'label' => 'Send me an application with credentials', 'code' => 'advert'],
        ];

        $positionTypes = [
            ['name' => 'Freelance / Casual', 'code' => 'casual', 'is_default' => true],
            ['name' => 'Full time', 'code' => 'full-time'],
            ['name' => 'Part time', 'code' => 'part-time'],
        ];

        $budgetTypes = [
            ['name' => 'Fixed', 'label' => "I'm looking for a fixed price", 'code' => 'fixed'],
            ['name' => 'Hourly', 'label' => "I'll pay by the hour", 'code' => 'hourly'],
            ['name' => 'Not specified', 'label' => "I'd rather not say", 'code' => 'unknown'],
        ];

        $budgetFixed = [
            ['name' => '$10 - $30', 'label' => 'Quick Task ($10 - $30)'],
            ['name' => '$30 - $250', 'label' => 'Basic Task ($30 - $250)'],
            ['name' => '$250 - $750', 'label' => 'Smaller Project ($250 - $750)'],
            ['name' => '$750 - $1500', 'label' => 'Small Project ($750 - $1500)', 'is_default' => true],
            ['name' => '$1500 - $3000', 'label' => 'Medium Project ($1500 - $3000)'],
            ['name' => '$3000 - $5000', 'label' => 'Large Project ($3000 - $5000)'],
            ['name' => '$5000+', 'label' => 'Larger Project ($5000+)'],
        ];

        $budgetHourly = [
            ['name' => '$2 - $8/hr', 'label' => 'Basic ($2 - $8/hr)'],
            ['name' => '$8 - $15/hr', 'label' => 'Moderate ($8 - $15/hr)'],
            ['name' => '$15 - $25/hr', 'label' => 'Standard ($15 - $25/hr)', 'is_default' => true],
            ['name' => '$25 - $50/hr', 'label' => 'Skilled ($25 - $50/hr)'],
            ['name' => '$50+/hr', 'label' => 'Expert ($50+/hr)'],
        ];

        $budgetTimeframe = [
            ['name' => "< 1 week"],
            ['name' => "1 - 4 weeks", 'is_default' => true],
            ['name' => "1 - 3 months"],
            ['name' => "3 - 6 months"],
            ['name' => "6 months+"],
            ['name' => 'Not specified', 'label' => "I don't know"],
        ];

        $bidType = [
            ['name' => 'Fixed', 'label' => 'Fixed rate', 'code' => ProjectBid::TYPE_FIXED, 'is_default' => true],
            ['name' => 'Hourly', 'label' => 'Hourly rate', 'code' => ProjectBid::TYPE_HOURLY],
        ];

        $workerBudget = [
            ['name' => '$3,000 and under'],
            ['name' => '$3,000-$10,000'],
            ['name' => '$10,000-$25,000'],
            ['name' => '$25,000-$50,000'],
            ['name' => 'Over $50,000'],
        ];

        $map = [
            Attribute::PROJECT_STATUS => $projectStatus,
            Attribute::PROJECT_TYPE => $projectTypes,
            Attribute::POSITION_TYPE => $positionTypes,
            Attribute::BUDGET_TYPE => $budgetTypes,
            Attribute::BUDGET_FIXED => $budgetFixed,
            Attribute::BUDGET_HOURLY => $budgetHourly,
            Attribute::BUDGET_TIMEFRAME => $budgetTimeframe,
            Attribute::BID_TYPE => $bidType,
            Attribute::WORKER_BUDGET => $workerBudget,
        ];

        foreach ($map as $type => $items) {
            foreach ($items as $data) {
                Attribute::create(array_merge($data, ['type' => $type]));
            }
        }

    }

}
