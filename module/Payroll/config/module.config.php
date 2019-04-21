<?php

namespace Payroll;

use Application\Controller\ControllerFactory;
use Payroll\Controller\FlatValue;
use Payroll\Controller\MonthlyValue;
use Payroll\Controller\Rules;
use Payroll\Controller\SalarySheetController;
use Payroll\Controller\TaxSheetController;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'monthlyValue' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/payroll/monthlyValue[/:action[/:id]]',
                    'defaults' => [
                        'controller' => MonthlyValue::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'flatValue' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/payroll/flatValue[/:action[/:id]]',
                    'defaults' => [
                        'controller' => FlatValue::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'rules' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/payroll/rules[/:action[/:id]]',
                    'defaults' => [
                        'controller' => Rules::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'salarySheet' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/payroll/salarysheet[/:action[/:id]]',
                    'defaults' => [
                        'controller' => SalarySheetController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'taxSheet' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/payroll/taxsheet[/:action[/:id]]',
                    'defaults' => [
                        'controller' => TaxSheetController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'varianceSetup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/payroll/varianceSetup[/:action[/:id]]',
                    'defaults' => [
                        'controller' => Controller\VarianceSetupController::class,
                        'action' => 'index'
                    ]
                ]
            ]
        ]
    ],
    'navigation' => [
        'monthlyValue' => [
            [
                'label' => 'Monthly Value',
                'route' => 'monthlyValue',
            ],
            [
                'label' => 'Monthly Value',
                'route' => 'monthlyValue',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'monthlyValue',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'monthlyValue',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'monthlyValue',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Employee Wise',
                        'route' => 'monthlyValue',
                        'action' => 'detail',
                    ],
                    [
                        'label' => 'Position Wise',
                        'route' => 'monthlyValue',
                        'action' => 'position-wise',
                    ],
                ]
            ]
        ],
        'flatValue' => [
            [
                'label' => 'Flat Value',
                'route' => 'flatValue',
            ],
            [
                'label' => 'Flat Value',
                'route' => 'flatValue',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'flatValue',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'flatValue',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'flatValue',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Employee Wise',
                        'route' => 'flatValue',
                        'action' => 'detail',
                    ],
                    [
                        'label' => 'Position Wise',
                        'route' => 'flatValue',
                        'action' => 'position-wise',
                    ],
                ]
            ]
        ],
        'rules' => [
            [
                'label' => 'Rules',
                'route' => 'rules',
            ],
            [
                'label' => 'Rules',
                'route' => 'rules',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'rules',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'rules',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'rules',
                        'action' => 'edit',
                    ],
                ]
            ]
        ], 'salarySheet' => [
            [
                'label' => 'Salary',
                'route' => 'salarySheet',
            ],
            [
                'label' => 'Salary',
                'route' => 'salarySheet',
                'pages' => [
                    [
                        'label' => 'Generate',
                        'route' => 'salarySheet',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Pay Value Modified',
                        'route' => 'salarySheet',
                        'action' => 'pay-value-modified',
                    ],
                    [
                        'label' => 'Payslip',
                        'route' => 'salarySheet',
                        'action' => 'payslip',
                    ],
                ]
            ]
        ], 'taxSheet' => [
            [
                'label' => 'Tax',
                'route' => 'taxSheet',
            ],
            [
                'label' => 'Tax',
                'route' => 'taxSheet',
                'pages' => [
                    [
                        'label' => 'Tax Sheet',
                        'route' => 'taxSheet',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Taxslip',
                        'route' => 'taxSheet',
                        'action' => 'taxslip',
                    ],
                ]
            ]
        ], 'varianceSetup' => [
            [
                'label' => 'Variance',
                'route' => 'varianceSetup',
            ],
            [
                'label' => 'Variance',
                'route' => 'varianceSetup',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'varianceSetup',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'varianceSetup',
                        'action' => 'add',
                    ],
                ]
            ]
        ]
    ],
    'controllers' => [
        'factories' => [
            MonthlyValue::class => ControllerFactory::class,
            FlatValue::class => ControllerFactory::class,
            Rules::class => ControllerFactory::class,
            SalarySheetController::class => ControllerFactory::class,
            TaxSheetController::class => ControllerFactory::class,
            Controller\VarianceSetupController::class => ControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];


