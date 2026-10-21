<?php

namespace Database\Seeders;

use App\Models\SearchSynonym;
use Illuminate\Database\Seeder;

/** Medical abbreviation dictionary used to expand searches (editable under SEO › Search dictionary). */
class SynonymSeeder extends Seeder
{
    public const TERMS = [
        'csr' => 'central serous chorioretinopathy, central serous retinopathy, cscr',
        'amd' => 'age-related macular degeneration, macular degeneration, armd',
        'namd' => 'neovascular amd, wet amd, exudative amd, neovascular age-related macular degeneration',
        'ga' => 'geographic atrophy',
        'dme' => 'diabetic macular oedema, diabetic macular edema, dmo',
        'dr' => 'diabetic retinopathy',
        'pdr' => 'proliferative diabetic retinopathy',
        'rvo' => 'retinal vein occlusion',
        'crvo' => 'central retinal vein occlusion',
        'brvo' => 'branch retinal vein occlusion',
        'crao' => 'central retinal artery occlusion',
        'erm' => 'epiretinal membrane, macular pucker',
        'mh' => 'macular hole, full-thickness macular hole, ftmh',
        'lmh' => 'lamellar macular hole',
        'vmt' => 'vitreomacular traction',
        'vma' => 'vitreomacular adhesion',
        'ird' => 'inherited retinal disease, inherited retinal dystrophy',
        'rp' => 'retinitis pigmentosa',
        'xlrs' => 'x-linked retinoschisis',
        'vkh' => 'vogt-koyanagi-harada, vogt–koyanagi–harada disease',
        'mewds' => 'multiple evanescent white dot syndrome',
        'pcv' => 'polypoidal choroidal vasculopathy',
        'cnv' => 'choroidal neovascularisation, choroidal neovascularization, mnv',
        'mnv' => 'macular neovascularisation, macular neovascularization, cnv',
        'srf' => 'subretinal fluid',
        'irf' => 'intraretinal fluid, intraretinal cysts',
        'ped' => 'pigment epithelial detachment, pigment epithelium detachment',
        'shrm' => 'subretinal hyperreflective material',
        'ez' => 'ellipsoid zone, is/os junction',
        'elm' => 'external limiting membrane',
        'rpe' => 'retinal pigment epithelium',
        'cme' => 'cystoid macular oedema, cystoid macular edema, cmo',
        'umo' => 'uveitic macular oedema, uveitic macular edema',
        'oedema' => 'edema',
        'octa' => 'oct angiography, oct-a',
        'faf' => 'fundus autofluorescence',
        'ffa' => 'fundus fluorescein angiography, fluorescein angiography, fa',
        'icga' => 'indocyanine green angiography',
        'uwf' => 'ultra-widefield, ultra widefield',
        'mactel' => 'macular telangiectasia',
        'rd' => 'retinal detachment',
        'pvr' => 'proliferative vitreoretinopathy',
        'anti-vegf' => 'anti vegf, ranibizumab, aflibercept, faricimab, bevacizumab',
        'frcophth' => 'frcophth part 1, frcophth part 2, royal college of ophthalmologists',
        'sba' => 'single best answer, mcq',
    ];

    public function run(): void
    {
        foreach (self::TERMS as $term => $synonyms) {
            SearchSynonym::updateOrCreate(['term' => $term], ['synonyms' => $synonyms, 'is_active' => true]);
        }
    }
}
