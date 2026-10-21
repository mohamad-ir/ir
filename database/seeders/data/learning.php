<?php

/** Sample courses, webinars and membership plans (synthetic; removable with the rest of the demo content). */
return [
    'plans' => [
        ['name' => 'Monthly', 'slug' => 'monthly', 'tagline' => 'Flexible — cancel any time', 'price' => 1500, 'prices' => [['currency' => 'GBP', 'amount' => 1300], ['currency' => 'USD', 'amount' => 1600]], 'interval' => 'month', 'trial_days' => 0, 'is_featured' => false, 'sort_order' => 1,
            'features' => ['Every members-only case and discussion', 'All courses included in membership', 'Free places at live webinars', 'Webinar recordings library', 'Full exam question bank']],
        ['name' => 'Yearly', 'slug' => 'yearly', 'tagline' => 'Best value for exam preparation', 'price' => 12000, 'prices' => [['currency' => 'GBP', 'amount' => 10000], ['currency' => 'USD', 'amount' => 13000]], 'interval' => 'year', 'trial_days' => 14, 'is_featured' => true, 'sort_order' => 2,
            'features' => ['Everything in Monthly', 'Two months free compared with monthly', '14-day free trial', 'Priority answers to case questions in webinars']],
    ],
    'courses' => [
        [
            'title' => 'Systematic OCT Interpretation', 'slug' => 'systematic-oct-interpretation',
            'subtitle' => 'A layer-by-layer method for reading any macular OCT with confidence',
            'summary' => 'Learn a reproducible eight-step approach to macular OCT, from scan quality to fluid compartments, with worked clinical examples.',
            'description' => '<p>Most OCT errors are errors of method rather than knowledge. This course teaches a structured approach you can apply in clinic tomorrow — the same sequence every time, so nothing is missed.</p><p>Each lesson combines a short video with annotated scans and a summary you can revisit before clinic or exams.</p>',
            'level' => 'beginner', 'price' => 9900, 'prices' => [['currency' => 'GBP', 'amount' => 8500], ['currency' => 'USD', 'amount' => 10900]], 'included_in_membership' => true, 'is_featured' => true, 'image' => 'oct-normal.jpg', 'topics' => ['amd', 'dme', 'csr'],
            'outcomes' => ['Assess scan quality and recognise common artefacts', 'Identify every retinal layer and the outer retinal bands', 'Distinguish intraretinal, subretinal and sub-RPE fluid', 'Build a differential diagnosis from OCT patterns', 'Report OCT findings clearly for colleagues and exams'],
            'faqs' => [['question' => 'Who is this course for?', 'answer' => 'Residents and fellows starting medical retina, optometrists working in shared-care clinics and anyone preparing for FRCOphth.'], ['question' => 'How long do I have access?', 'answer' => 'Purchased courses stay available for as long as the course is offered. Members have access while their membership is active.']],
            'sections' => [
                ['title' => 'Foundations', 'lessons' => [
                    ['title' => 'Welcome and how to use this course', 'minutes' => 4, 'preview' => true, 'summary' => 'What you will learn and how the lessons are organised.'],
                    ['title' => 'Scan quality and artefacts', 'minutes' => 9, 'preview' => true, 'summary' => 'Signal strength, segmentation errors and mirror artefacts.'],
                    ['title' => 'The normal macula, layer by layer', 'minutes' => 12, 'summary' => 'From the vitreoretinal interface to the choroid.'],
                ]],
                ['title' => 'Fluid and its meaning', 'lessons' => [
                    ['title' => 'Subretinal fluid', 'minutes' => 10, 'summary' => 'CSR, nAMD and other causes of serous detachment.', 'quiz' => ['pass_mark' => 70, 'questions' => [
                        ['question' => 'Optically empty fluid between the neurosensory retina and the RPE is best described as:', 'options' => [
                            ['text' => 'Subretinal fluid', 'correct' => true], ['text' => 'Intraretinal fluid', 'correct' => false],
                            ['text' => 'Sub-RPE fluid', 'correct' => false], ['text' => 'Vitreous fluid', 'correct' => false]],
                            'explanation' => 'Subretinal fluid lies between the photoreceptor outer segments and the RPE; it is typical of CSR and active nAMD.'],
                        ['question' => 'Which features favour central serous chorioretinopathy? (select all that apply)', 'options' => [
                            ['text' => 'Thick choroid', 'correct' => true], ['text' => 'Smooth dome of subretinal fluid', 'correct' => true],
                            ['text' => 'Large haemorrhagic PED in an 85-year-old', 'correct' => false], ['text' => 'Hard exudate ring', 'correct' => false]],
                            'explanation' => 'Pachychoroid and a smooth serous detachment in a younger patient point to CSR; haemorrhage and exudates suggest other diagnoses.'],
                    ]]],
                    ['title' => 'Intraretinal fluid and cysts', 'minutes' => 11, 'summary' => 'DME, RVO and the pitfalls of degenerative cysts.'],
                    ['title' => 'Pigment epithelial detachments', 'minutes' => 8, 'summary' => 'Serous, drusenoid and fibrovascular PEDs.'],
                ]],
                ['title' => 'Putting it together', 'lessons' => [
                    ['title' => 'The eight-step reporting template', 'minutes' => 7, 'summary' => 'A reusable structure for clinic letters and vivas.'],
                    ['title' => 'Worked cases', 'minutes' => 15, 'summary' => 'Three cases from first scan to diagnosis.'],
                ]],
            ],
        ],
        [
            'title' => 'Uveitis Imaging Essentials', 'slug' => 'uveitis-imaging-essentials',
            'subtitle' => 'A short, free introduction to multimodal imaging in uveitis',
            'summary' => 'Three short lessons on OCT, fluorescein and ICG angiography in inflammatory eye disease.',
            'description' => '<p>A concise primer on the imaging that matters in uveitis clinic: what each modality shows, when to request it, and how to recognise inflammatory macular oedema.</p>',
            'level' => 'intermediate', 'price' => 0, 'included_in_membership' => true, 'is_featured' => false, 'image' => 'oct-uveitic-cme.jpg', 'topics' => ['uveitis'],
            'outcomes' => ['Choose the right imaging test for each uveitis presentation', 'Recognise uveitic macular oedema on OCT', 'Understand the role of ICG angiography in choroiditis'],
            'faqs' => [],
            'sections' => [
                ['title' => 'Imaging in uveitis', 'lessons' => [
                    ['title' => 'OCT in uveitis', 'minutes' => 8, 'summary' => 'Macular oedema, serous detachments and choroidal thickness.'],
                    ['title' => 'Fluorescein angiography', 'minutes' => 9, 'summary' => 'Vasculitis, leakage and disc staining.'],
                    ['title' => 'ICG angiography', 'minutes' => 7, 'summary' => 'Hypofluorescent dark dots and choroidal involvement.', 'quiz' => ['pass_mark' => 50, 'questions' => [
                        ['question' => 'Hypofluorescent dark dots on ICG angiography in uveitis usually indicate:', 'options' => [
                            ['text' => 'Choroidal stromal inflammation', 'correct' => true], ['text' => 'Retinal vasculitis', 'correct' => false], ['text' => 'Normal finding', 'correct' => false]],
                            'explanation' => 'Hypofluorescent dark dots reflect choroidal stromal granulomas or choriocapillaris non-perfusion.'],
                    ]]],
                ]],
            ],
        ],
    ],
    'webinars' => [
        ['title' => 'Live case review: difficult OCTs in medical retina', 'slug' => 'live-case-review-difficult-octs',
            'summary' => 'Bring your questions — we work through challenging macular OCTs together, with live polling and Q&A.',
            'description' => '<p>An interactive session for trainees and clinicians. We will review a series of challenging scans, discuss differentials and management, and answer your questions live.</p><ul><li>Pachychoroid spectrum versus nAMD</li><li>Degenerative cysts versus active fluid</li><li>Vitreomacular interface disease</li></ul>',
            'days' => 10, 'hour' => 18, 'minutes' => 75, 'price' => 2500, 'prices' => [['currency' => 'GBP', 'amount' => 2200], ['currency' => 'USD', 'amount' => 2900]], 'free_for_members' => true, 'capacity' => 300, 'image' => 'oct-pachychoroid.jpg', 'topics' => ['csr', 'amd']],
        ['title' => 'FRCOphth viva technique: open Q&A', 'slug' => 'frcophth-viva-technique-qa',
            'summary' => 'A free open session on structuring viva answers, with examples and audience questions.',
            'description' => '<p>How to structure answers under pressure, how examiners think, and how to recover when you are unsure. Free for everyone with an account.</p>',
            'days' => 24, 'hour' => 19, 'minutes' => 60, 'price' => 0, 'free_for_members' => true, 'capacity' => null, 'image' => 'fundus-normal.jpg', 'topics' => []],
    ],
];
