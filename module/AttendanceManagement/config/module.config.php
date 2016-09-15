<?php
/**
 * Created by PhpStorm.
 * User: punam
 * Date: 9/13/16
 * Time: 11:07 AM
 */
namespace AttendanceManagement;

use Application\Controller\ControllerFactory;
use Zend\Router\Http\Segment;


return [
    'router'=>[
        'routes'=>[
            'shiftassign' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/shiftassign[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ShiftAssign::class,
                        'action' => 'index',
                    ]
                ],
            ],

            'attendancebyhr' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/attendancebyhr[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\AttendanceByHr::class,
                        'action' => 'index',
                    ]
                ],
            ],

            'shiftsetup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/shiftsetup[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ShiftSetup::class,
                        'action' => 'index',
                    ]
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\ShiftAssign::class=>ControllerFactory::class,
            Controller\AttendanceByHr::class=>ControllerFactory::class,
            Controller\ShiftSetup::class=>ControllerFactory::class,
        ],

    ],

    'view_manager'=>[
        'template_path_stack'=>[
            __DIR__.'/../view',
        ]
    ]
];

