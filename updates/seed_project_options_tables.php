<?php namespace Ahoy\Pyrolancer\Updates;

use October\Rain\Database\Updates\Seeder;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Ahoy\Pyrolancer\Models\ProjectBid;
use Ahoy\Pyrolancer\Models\ProjectOption;

class SeedProjectOptionsTables extends Seeder
{

    public function run()
    {
        $projectStatus = [
            ['name' => 'Draft', 'code' => ProjectModel::STATUS_DRAFT],
            ['name' => 'Pending', 'code' => ProjectModel::STATUS_PENDING],
            ['name' => 'Rejected', 'code' => ProjectModel::STATUS_REJECTED],
            ['name' => 'Active', 'code' => ProjectModel::STATUS_ACTIVE],
            ['name' => 'Suspended', 'code' => ProjectModel::STATUS_SUSPENDED],
            ['name' => 'Closed', 'code' => ProjectModel::STATUS_CLOSED],
            ['name' => 'Cancelled', 'code' => ProjectModel::STATUS_CANCELLED],
            ['name' => 'Expired', 'code' => ProjectModel::STATUS_EXPIRED],
        ];

        $projectTypes = [
            ['name' => 'Give an estimate on how much it will cost', 'code' => 'auction'],
            ['name' => 'Send an application with their credentials', 'code' => 'advert'],
        ];

        $positionTypes = [
            ['name' => 'Freelance / Casual', 'code' => 'casual', 'is_default' => true],
            ['name' => 'Full time', 'code' => 'parttime'],
            ['name' => 'Part time', 'code' => 'fulltime'],
        ];

        $budgetTypes = [
            ['name' => "I'm looking for a fixed price", 'code' => 'fixed'],
            ['name' => "I'll pay by the hour", 'code' => 'hourly'],
            ['name' => "I'd rather not say", 'code' => 'unknown'],
        ];

        $budgetFixed = [
            ['name' => 'Quick Task ($10 - $30)'],
            ['name' => 'Basic Task ($30 - $250)'],
            ['name' => 'Smaller Project ($250 - $750)'],
            ['name' => 'Small Project ($750 - $1500)', 'is_default' => true],
            ['name' => 'Medium Project ($1500 - $3000)'],
            ['name' => 'Large Project ($3000 - $5000)'],
            ['name' => 'Larger Project ($5000+)'],
        ];

        $budgetHourly = [
            ['name' => 'Basic ($2 - $8/hr)'],
            ['name' => 'Moderate ($8 - $15/hr)'],
            ['name' => 'Standard ($15 - $25/hr)', 'is_default' => true],
            ['name' => 'Skilled ($25 - $50/hr)'],
            ['name' => 'Expert ($50+/hr)'],
        ];

        $budgetTimeframe = [
            ['name' => "< 1 week"],
            ['name' => "1 - 4 weeks", 'is_default' => true],
            ['name' => "1 - 3 months"],
            ['name' => "3 - 6 months"],
            ['name' => "6 months+"],
            ['name' => "I don't know"],
        ];

        $bidStatus = [
            ['name' => 'Draft', 'code' => ProjectBid::STATUS_DRAFT],
            ['name' => 'Active', 'code' => ProjectBid::STATUS_ACTIVE],
            ['name' => 'Hidden', 'code' => ProjectBid::STATUS_HIDDEN],
            ['name' => 'Short listed', 'code' => ProjectBid::STATUS_SHORTLISTED],
            ['name' => 'Accepted', 'code' => ProjectBid::STATUS_ACCEPTED],
        ];

        $map = [
            ProjectOption::PROJECT_STATUS => $projectStatus,
            ProjectOption::PROJECT_TYPE => $projectTypes,
            ProjectOption::POSITION_TYPE => $positionTypes,
            ProjectOption::BUDGET_TYPE => $budgetTypes,
            ProjectOption::BUDGET_FIXED => $budgetFixed,
            ProjectOption::BUDGET_HOURLY => $budgetHourly,
            ProjectOption::BUDGET_TIMEFRAME => $budgetTimeframe,
            ProjectOption::BID_STATUS => $bidStatus,
        ];

        foreach ($map as $type => $items) {
            foreach ($items as $data) {
                ProjectOption::create(array_merge($data, ['type' => $type]));
            }
        }

    }

}
