<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            'header' => ['Main navigation', [
                ['label' => 'OCT cases', 'url' => '/cases', 'children' => [
                    ['label' => 'Case library', 'url' => '/cases', 'description' => 'Browse every case by disease, imaging and difficulty'],
                    ['label' => 'Case of the Week', 'url' => '/case-of-the-week', 'description' => 'Commit to a diagnosis, then reveal the answer'],
                    ['label' => 'Topics A–Z', 'url' => '/topics', 'description' => 'AMD, DME, CSR, ERM, macular hole, uveitis…'],
                ]],
                ['label' => 'Videos', 'url' => '/videos'],
                ['label' => 'Articles', 'url' => '/articles'],
                ['label' => 'Exam prep', 'url' => '/exam-prep', 'children' => [
                    ['label' => 'FRCS / FRCOphth topics', 'url' => '/exam-prep', 'description' => 'Revision notes and exam tips'],
                    ['label' => 'Question bank', 'url' => '/exam-prep/practice', 'description' => 'SBA, image-based and viva practice'],
                ]],
                ['label' => 'About', 'url' => '/about'],
                ['label' => 'Contact', 'url' => '/contact'],
            ]],
            'header_cta' => ['Header buttons', [
                ['label' => 'Weekly case', 'url' => '/newsletter'],
            ]],
            'footer_primary' => ['Learn', [
                ['label' => 'OCT case library', 'url' => '/cases'],
                ['label' => 'Case of the Week', 'url' => '/case-of-the-week'],
                ['label' => 'Video library', 'url' => '/videos'],
                ['label' => 'Articles', 'url' => '/articles'],
                ['label' => 'Topics A–Z', 'url' => '/topics'],
            ]],
            'footer_secondary' => ['Exam preparation', [
                ['label' => 'FRCOphth & FRCS topics', 'url' => '/exam-prep'],
                ['label' => 'Question bank', 'url' => '/exam-prep/practice'],
                ['label' => 'Viva practice', 'url' => '/exam-prep/practice?type=viva'],
                ['label' => 'Image-based questions', 'url' => '/exam-prep/practice?type=image'],
            ]],
            'footer_tertiary' => ['About', [
                ['label' => 'About', 'url' => '/about'],
                ['label' => 'Collaboration & contact', 'url' => '/contact'],
                ['label' => 'Newsletter', 'url' => '/newsletter'],
                ['label' => 'Courses (coming soon)', 'url' => '/courses'],
            ]],
            'footer_legal' => ['Legal', [
                ['label' => 'Privacy', 'url' => '/privacy'],
                ['label' => 'Terms of use', 'url' => '/terms'],
                ['label' => 'Medical disclaimer', 'url' => '/disclaimer'],
            ]],
        ];

        foreach ($menus as $location => [$name, $items]) {
            Menu::firstOrCreate(['location' => $location], ['name' => $name, 'items' => $items]);
        }
    }
}
