<?php

/** Sample photo galleries built from the synthetic demo images. */
return [
    ['title' => 'Macular OCT atlas', 'slug' => 'macular-oct-atlas', 'layout' => 'justified', 'dark' => true, 'featured' => true, 'topics' => ['amd', 'dme', 'csr'],
        'summary' => 'Annotated B-scans of the most common macular diseases, from the normal fovea to neovascular AMD.',
        'images' => [
            ['oct-normal.jpg', 'Normal macula: foveal pit and continuous outer retinal bands.'],
            ['oct-drusen.jpg', 'Drusen: dome-shaped elevations of the RPE.'],
            ['oct-namd.jpg', 'Neovascular AMD with subretinal fluid and a fibrovascular PED.'],
            ['oct-csr.jpg', 'Central serous chorioretinopathy: dome of optically empty subretinal fluid.'],
            ['oct-csr-resolved.jpg', 'The same eye after resolution of the subretinal fluid.'],
            ['oct-dme.jpg', 'Diabetic macular oedema with intraretinal cystoid spaces.'],
            ['oct-rvo.jpg', 'Macular oedema secondary to retinal vein occlusion.'],
            ['oct-pachychoroid.jpg', 'Pachychoroid with dilated outer choroidal vessels.'],
        ]],
    ['title' => 'Vitreoretinal interface', 'slug' => 'vitreoretinal-interface', 'layout' => 'grid', 'dark' => true, 'featured' => false, 'topics' => ['erm', 'mh'],
        'summary' => 'Epiretinal membrane, vitreomacular traction and macular holes on OCT.',
        'images' => [
            ['oct-erm.jpg', 'Epiretinal membrane with loss of the foveal contour.'],
            ['oct-vmt.jpg', 'Vitreomacular traction distorting the fovea.'],
            ['oct-macular-hole.jpg', 'Full-thickness macular hole with cystic edges.'],
        ]],
    ['title' => 'Colour fundus collection', 'slug' => 'colour-fundus-collection', 'layout' => 'masonry', 'dark' => false, 'featured' => false, 'topics' => ['uveitis'],
        'summary' => 'Fundus photographs across medical retina and uveitis.',
        'images' => [
            ['fundus-normal.jpg', 'Normal fundus.'],
            ['fundus-amd.jpg', 'Age-related macular degeneration.'],
            ['fundus-dr.jpg', 'Diabetic retinopathy.'],
            ['fundus-crvo.jpg', 'Central retinal vein occlusion.'],
            ['fundus-csr.jpg', 'Central serous chorioretinopathy.'],
            ['fundus-vasculitis.jpg', 'Retinal vasculitis.'],
        ]],
];
