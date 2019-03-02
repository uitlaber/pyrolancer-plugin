<?php namespace Responsiv\Pyrolancer\Updates;

use October\Rain\Database\Updates\Seeder;
use Responsiv\Pyrolancer\Models\Project as ProjectModel;
use Responsiv\Pyrolancer\Models\ProjectBid;
use Responsiv\Pyrolancer\Models\PortfolioItem;
use Responsiv\Pyrolancer\Models\Attribute;

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
            ['name' => '$10 - $30', 'code' => '10-to-30', 'label' => 'Quick Task ($10 - $30)'],
            ['name' => '$30 - $250', 'code' => '30-to-250', 'label' => 'Basic Task ($30 - $250)'],
            ['name' => '$250 - $750', 'code' => '250-to-750', 'label' => 'Smaller Project ($250 - $750)'],
            ['name' => '$750 - $1500', 'code' => '750-to-1500', 'label' => 'Small Project ($750 - $1500)', 'is_default' => true],
            ['name' => '$1500 - $3000', 'code' => '1500-to-3000', 'label' => 'Medium Project ($1500 - $3000)'],
            ['name' => '$3000 - $5000', 'code' => '3000-to-5000', 'label' => 'Large Project ($3000 - $5000)'],
            ['name' => '$5000+', 'code' => '5000-above', 'label' => 'Larger Project ($5000+)'],
        ];

        $budgetHourly = [
            ['name' => '$2 - $8/hr', 'code' => '2-to-8-hr', 'label' => 'Basic ($2 - $8/hr)'],
            ['name' => '$8 - $15/hr', 'code' => '8-to-15-hr', 'label' => 'Moderate ($8 - $15/hr)'],
            ['name' => '$15 - $25/hr', 'code' => '15-to-25-hr', 'label' => 'Standard ($15 - $25/hr)', 'is_default' => true],
            ['name' => '$25 - $50/hr', 'code' => '25-to-50-hr', 'label' => 'Skilled ($25 - $50/hr)'],
            ['name' => '$50+/hr', 'code' => '50-above-hr', 'label' => 'Expert ($50+/hr)'],
        ];

        $budgetTimeframe = [
            ['name' => "< 1 week", 'code' => '1-week-below'],
            ['name' => "1 - 4 weeks", 'code' => '1-to-4-weeks', 'is_default' => true],
            ['name' => "1 - 3 months", 'code' => '1-to-3-months'],
            ['name' => "3 - 6 months", 'code' => '3-to-6-months'],
            ['name' => "6 months+", 'code' => '6-months-above'],
            ['name' => 'Not specified', 'code' => 'unspecified', 'label' => "I don't know"],
        ];

        $bidType = [
            ['name' => 'Fixed', 'label' => 'Fixed rate', 'code' => ProjectBid::TYPE_FIXED, 'is_default' => true],
            ['name' => 'Hourly', 'label' => 'Hourly rate', 'code' => ProjectBid::TYPE_HOURLY],
        ];

        $workerBudget = [
            ['name' => '$3,000 and under', 'code' => '3000-below'],
            ['name' => '$3,000-$10,000', 'code' => '3000-to-10000'],
            ['name' => '$10,000-$25,000', 'code' => '10000-to-25000'],
            ['name' => '$25,000-$50,000', 'code' => '25000-to-50000'],
            ['name' => 'Over $50,000', 'code' => '50000-above'],
        ];

        $portfolioTypes = [
            ['name' => 'Image', 'code' => PortfolioItem::TYPE_IMAGE],
            ['name' => 'Article', 'code' => PortfolioItem::TYPE_ARTICLE],
            ['name' => 'Link', 'code' => PortfolioItem::TYPE_LINK],
            ['name' => 'Audio', 'code' => PortfolioItem::TYPE_AUDIO],
            ['name' => 'Video', 'code' => PortfolioItem::TYPE_VIDEO],
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
            Attribute::PORTFOLIO_TYPE => $portfolioTypes,
        ];

        foreach ($map as $type => $items) {
            foreach ($items as $data) {
                Attribute::create(array_merge($data, ['type' => $type]));
            }
        }

    }

}
