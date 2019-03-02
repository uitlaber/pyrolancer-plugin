<?php namespace Responsiv\Pyrolancer\Updates;

use Responsiv\Pyrolancer\Models\Skill;
use Responsiv\Pyrolancer\Models\SkillCategory;
use Responsiv\Pyrolancer\Models\ProjectCategory;
use October\Rain\Database\Updates\Seeder;

class SeedCategoryAndSkillTables extends Seeder
{

    public function run()
    {
        /*
         * Project skills and categories
         */
        $skills = [
            'Web & Software Development' => [
                'HTML5',
                'CSS3',
                'JavaScript',
                'jQuery',
                'Bootstrap',
                'PHP',
                'Perl',
                'Java',
                'Ruby',
                'VBScript',
                'Cold Fusion',
                'C Programming',
                'C++ Programming',
                'C# Programming',
                'Objective C',
                'Software Development',
                'Git',
                'Joomla',
                'Drupal',
                'WordPress',
                'OctoberCMS',
                'MODx',
                'Magento',
                'LemonStand',
                'Yii',
                'CakePHP',
                'CodeIgniter',
                'Laravel',
                'Ruby on Rails',
                'Windows',
                'OS X',
                'Linux',
            ],
            'Mobile Development' => [
                'Mobile Development',
                'iOS',
                'iPhone',
                'iPad',
                'Android',
            ],
            'Graphic Design & Media' => [
                'Adobe Flash',
                'Photoshop',
                'PSD to HTML',
                'Illustrator',
                'Logo Design',
                'Web Design',
                'User Interface',
                'User Experience',
                'Graphic Design',
                'Business Cards',
                'Photography',
            ],
            'Writing & Content' => [
                'Copywriting',
                'Blogging',
                'Proofreading',
                'Ghostwriting',
                'eBooks',
                'Translation',
                'SEO',
            ],
            'Sales & Marketing' => [
                'Online Marketing',
                'Article Submission',
                'Web Scraping',
                'Social Networking',
                'Facebook Marketing',
                'Twitter Marketing',
                'Telemarketing',
            ],
            'Other' => [
                'System Admin',
                'Data Entry',
            ],
        ];

        foreach ($skills as $categoryName => $categorySkills) {
            $category = SkillCategory::create(['name' => $categoryName]);
            foreach ($categorySkills as $skill) {
                $category->skills()->create(['name' => $skill]);
            }
        }

        /*
         * Project categories
         */
        $categories = [
            'Websites & Web Applications' => [
                'Create a website' => ['PHP', 'HTML5', 'CSS3', 'Web Design', 'Graphic Design'],
                'Convert a template to a website' => ['HTML5', 'Photoshop', 'PSD to HTML', 'Web Design'],
            ],
            'Desktop Software' => [
                'Write software for Windows' => ['Software Development', 'Windows'],
                'Write software for Mac' => ['Software Development', 'OS X'],
                'Write software for Linux' => ['Software Development', 'Linux'],
            ],
            'Mobile Apps' => [
                'Write software for iPhone / iPad' => ['Mobile Development', 'iOS', 'iPhone', 'iPad'],
                'Write software for Android' => ['Mobile Development', 'Android'],
                'Create a mobile website' => ['HTML5', 'CSS3', 'jQuery']
            ],
            'Graphic Design' => [
                'Design a website mockup' => ['Graphic Design', 'Web Design', 'User Experience'],
                'Design a logo' => ['Graphic Design', 'Logo Design'],
                'Design some business cards' => ['Graphic Design', 'Business Cards'],
                'General graphic design' => ['Graphic Design'],
            ],
            'Writing' => [
                'Proofread some work' => ['Proofreading'],
                'Write a book' => ['Ghostwriting', 'Copywriting', 'eBooks'],
            ],
            'Data Entry' => [
                'Submit some articles' => ['Article Submission', 'Online Marketing'],
                'Enter data in a website' => ['Data Entry'],
                'Get data from websites' => ['Data Entry', 'Web Scraping'],
                'Write some blog posts' => ['Copywriting', 'Blogging'],
            ],
            'Sales & Marketing'  => [
                'SEO Optimize my website' => ['SEO', 'Online Marketing'],
                'Get me Facebook followers' => ['Facebook Marketing', 'Social Networking'],
                'Get me Twitter followers' => ['Twitter Marketing', 'Social Networking'],
                'Telemarket something' => ['Telemarketing'],
            ],
        ];

        foreach ($categories as $parentCategory => $childCategories) {
            $parent = ProjectCategory::create(['name' => $parentCategory]);

            foreach ($childCategories as $childCategory => $skills) {
                $child = ProjectCategory::create(['name' => $childCategory, 'parent_id' => $parent->id]);
                $skillIds = Skill::whereIn('name', $skills)->lists('id');
                $child->skills()->sync($skillIds);
            }

        }

    }

}
