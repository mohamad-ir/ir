<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Modality;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonomySeeder extends Seeder
{
    public const TOPICS = [
        ['Medical retina', null, '#12324f', [
            ['Age-related macular degeneration', 'AMD'],
            ['Neovascular AMD', 'nAMD'],
            ['Geographic atrophy', 'GA'],
            ['Polypoidal choroidal vasculopathy', 'PCV'],
            ['Diabetic macular oedema', 'DME'],
            ['Diabetic retinopathy', 'DR'],
            ['Central serous chorioretinopathy', 'CSR'],
            ['Pachychoroid spectrum', null],
            ['Retinal vein occlusion', 'RVO'],
            ['Retinal artery occlusion', 'RAO'],
            ['Macular telangiectasia type 2', 'MacTel'],
            ['Myopic maculopathy', null],
        ]],
        ['Vitreoretinal interface', null, '#0f8b8d', [
            ['Epiretinal membrane', 'ERM'],
            ['Macular hole', 'MH'],
            ['Lamellar macular hole', 'LMH'],
            ['Vitreomacular traction', 'VMT'],
        ]],
        ['Inherited retinal disease', 'IRD', '#6b4fa0', [
            ['Retinitis pigmentosa', 'RP'],
            ['Stargardt disease', null],
            ['Best vitelliform macular dystrophy', 'Best'],
            ['X-linked retinoschisis', 'XLRS'],
        ]],
        ['Uveitis', null, '#b4413b', [
            ['Vogt–Koyanagi–Harada disease', 'VKH'],
            ['Birdshot chorioretinopathy', null],
            ['Multiple evanescent white dot syndrome', 'MEWDS'],
            ['Uveitic macular oedema', 'UMO'],
            ['Toxoplasma retinochoroiditis', null],
            ['Serpiginous choroiditis', null],
        ]],
        ['Surgical retina', null, '#d9822b', [
            ['Retinal detachment', 'RD'],
            ['Proliferative vitreoretinopathy', 'PVR'],
        ]],
        ['Optic nerve', null, '#3a6b35', [
            ['Glaucoma (RNFL & GCL analysis)', null],
            ['Optic disc swelling', null],
        ]],
    ];

    public const FEATURED = ['AMD', 'nAMD', 'DME', 'CSR', 'ERM', 'MH', 'RVO', 'IRD', 'VKH', 'Uveitis', 'VMT', 'RP'];

    public function run(): void
    {
        foreach (self::TOPICS as $i => [$name, $abbr, $color, $children]) {
            $parent = Topic::firstOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'abbreviation' => $abbr, 'color' => $color, 'sort_order' => $i,
                'is_featured' => in_array($abbr ?? $name, self::FEATURED, true),
                'description' => "Teaching cases, articles and videos on {$name}.",
            ]);
            foreach ($children as $j => [$childName, $childAbbr]) {
                Topic::firstOrCreate(['slug' => Str::slug($childName)], [
                    'parent_id' => $parent->id, 'name' => $childName, 'abbreviation' => $childAbbr, 'sort_order' => $j,
                    'color' => $color, 'is_featured' => in_array($childAbbr, self::FEATURED, true),
                    'description' => "OCT and multimodal imaging teaching cases on {$childName}".($childAbbr ? " ({$childAbbr})" : '').', with discussion and clinical pearls.',
                ]);
            }
        }

        foreach ([
            ['Spectral-domain OCT', 'OCT'], ['OCT angiography', 'OCT-A'], ['En face OCT', 'En face'],
            ['Colour fundus photography', 'CFP'], ['Ultra-widefield imaging', 'UWF'], ['Fundus autofluorescence', 'FAF'],
            ['Fundus fluorescein angiography', 'FFA'], ['Indocyanine green angiography', 'ICGA'], ['B-scan ultrasonography', 'B-scan'],
        ] as $i => [$name, $abbr]) {
            Modality::firstOrCreate(['slug' => Str::slug($abbr)], ['name' => $name, 'abbreviation' => $abbr, 'sort_order' => $i]);
        }

        foreach (['OCT interpretation', 'Medical retina', 'Uveitis', 'Surgical retina', 'Clinical approach', 'Exam preparation'] as $i => $name) {
            Category::firstOrCreate(['type' => 'article', 'slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $i]);
        }
        foreach (['OCT interpretation', 'Retina', 'Uveitis', 'Surgical cases', 'Clinical examination', 'Case discussion'] as $i => $name) {
            Category::firstOrCreate(['type' => 'video', 'slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $i]);
        }
    }
}
