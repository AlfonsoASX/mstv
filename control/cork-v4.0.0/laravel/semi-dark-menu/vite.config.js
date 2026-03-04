import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [

                /**
                 * ================================
                 *      Config images Files
                 * =================================
                 */
                'resources/js/laravel-app.js',


                /**
                 * ================================
                 *      Project Base Javascript Files
                 * =================================
                 */
                
                // 'resources/global-plugins/bootstrap/js/bootstrap.bundle.min.js',
                // 'resources/global-plugins/perfect-scrollbar/perfect-scrollbar.min.js',
                // 'resources/global-plugins/mousetrap/mousetrap.min.js',
                // 'resources/global-plugins/waves/waves.min.js',
                
                /**
                 * =======================
                 *      Assets Files
                 * =======================
                 */

                // Loader
                // 'resources/scss/layouts/modern-light-menu/light/loader.scss',

                // 'resources/layouts/modern-light-menu/loader.js',
                // 'resources/layouts/modern-dark-menu/loader.js',
                // 'resources/layouts/collapsible-menu/loader.js',
                // 'resources/layouts/horizontal-light-menu/loader.js',
                // 'resources/layouts/horizontal-dark-menu/loader.js',
                'resources/layouts/semi-dark-menu/loader.js',
                // 'resources/layouts/vertical-dark-menu/loader.js',
                // 'resources/layouts/vertical-light-menu/loader.js',
                
                // Structure

                // Modern Light Menu
                // 'resources/scss/layouts/modern-light-menu/light/structure.scss',
                // 'resources/scss/layouts/modern-light-menu/dark/structure.scss',
                // 'resources/scss/layouts/modern-light-menu/light/loader.scss',
                // 'resources/scss/layouts/modern-light-menu/dark/loader.scss',

                // Modern Dark Menu
                // 'resources/scss/layouts/modern-dark-menu/light/structure.scss',
                // 'resources/scss/layouts/modern-dark-menu/dark/structure.scss',
                // 'resources/scss/layouts/modern-light-menu/light/loader.scss',
                // 'resources/scss/layouts/modern-light-menu/dark/loader.scss',

                // Collapsible Menu
                // 'resources/scss/layouts/collapsible-menu/light/structure.scss',
                // 'resources/scss/layouts/collapsible-menu/dark/structure.scss',
                // 'resources/scss/layouts/collapsible-menu/light/loader.scss',
                // 'resources/scss/layouts/collapsible-menu/dark/loader.scss',

                // Horizontal Light Menu
                // 'resources/scss/layouts/horizontal-light-menu/light/structure.scss',
                // 'resources/scss/layouts/horizontal-light-menu/dark/structure.scss',
                // 'resources/scss/layouts/horizontal-light-menu/light/loader.scss',
                // 'resources/scss/layouts/horizontal-light-menu/dark/loader.scss',

                // Horizontal Dark Menu
                // 'resources/scss/layouts/horizontal-dark-menu/light/structure.scss',
                // 'resources/scss/layouts/horizontal-dark-menu/dark/structure.scss',
                // 'resources/scss/layouts/horizontal-dark-menu/light/loader.scss',
                // 'resources/scss/layouts/horizontal-dark-menu/dark/loader.scss',


                // Semi - Dark Menu
                'resources/scss/layouts/semi-dark-menu/light/structure.scss',
                'resources/scss/layouts/semi-dark-menu/dark/structure.scss',
                'resources/scss/layouts/semi-dark-menu/light/loader.scss',
                'resources/scss/layouts/semi-dark-menu/dark/loader.scss',

                // Vertical Light Menu
                // 'resources/scss/layouts/vertical-light-menu/light/structure.scss',
                // 'resources/scss/layouts/vertical-light-menu/dark/structure.scss',
                // 'resources/scss/layouts/vertical-light-menu/light/loader.scss',
                // 'resources/scss/layouts/vertical-light-menu/dark/loader.scss',

                // Vertical Dark Menu
                // 'resources/scss/layouts/vertical-dark-menu/light/structure.scss',
                // 'resources/scss/layouts/vertical-dark-menu/dark/structure.scss',
                // 'resources/scss/layouts/vertical-dark-menu/light/loader.scss',
                // 'resources/scss/layouts/vertical-dark-menu/dark/loader.scss',
                
                
                // Main
                'resources/scss/light/assets/main.scss',
                'resources/scss/dark/assets/main.scss',

                // Secondary Files
                'resources/scss/light/assets/scrollspyNav.scss',
                'resources/scss/light/assets/custom.scss',

                'resources/scss/dark/assets/scrollspyNav.scss',
                'resources/scss/dark/assets/custom.scss',
                
                // Assets Files

                /**
                 * Light
                 */

                // --- Apps
                'resources/scss/light/assets/apps/blog-create.scss',
                'resources/scss/light/assets/apps/blog-post.scss',
                'resources/scss/light/assets/apps/chat.scss',
                'resources/scss/light/assets/apps/contacts.scss',
                'resources/scss/light/assets/apps/ecommerce-create.scss',
                'resources/scss/light/assets/apps/ecommerce-details.scss',
                'resources/scss/light/assets/apps/invoice-add.scss',
                'resources/scss/light/assets/apps/invoice-edit.scss',
                'resources/scss/light/assets/apps/invoice-list.scss',
                'resources/scss/light/assets/apps/invoice-preview.scss',
                'resources/scss/light/assets/apps/mailbox.scss',
                'resources/scss/light/assets/apps/notes.scss',
                'resources/scss/light/assets/apps/scrumboard.scss',
                'resources/scss/light/assets/apps/todolist.scss',
                
                // --- Authentication
                'resources/scss/light/assets/authentication/auth-boxed.scss',
                'resources/scss/light/assets/authentication/auth-cover.scss',

                
                // --- Componenets
                'resources/scss/light/assets/components/accordions.scss',
                'resources/scss/light/assets/components/carousel.scss',
                'resources/scss/light/assets/components/flags.scss',
                'resources/scss/light/assets/components/font-icons.scss',
                'resources/scss/light/assets/components/list-group.scss',
                'resources/scss/light/assets/components/media_object.scss',
                'resources/scss/light/assets/components/modal.scss',
                'resources/scss/light/assets/components/tabs.scss',
                'resources/scss/light/assets/components/timeline.scss',

                // --- Dashbaord
                'resources/scss/light/assets/dashboard/dash_1.scss',
                'resources/scss/light/assets/dashboard/dash_2.scss',
                
                // --- Elements
                'resources/scss/light/assets/elements/alert.scss',
                'resources/scss/light/assets/elements/color_library.scss',
                'resources/scss/light/assets/elements/custom-pagination.scss',
                'resources/scss/light/assets/elements/custom-tree_view.scss',
                'resources/scss/light/assets/elements/custom-typography.scss',
                'resources/scss/light/assets/elements/infobox.scss',
                'resources/scss/light/assets/elements/popover.scss',
                'resources/scss/light/assets/elements/search.scss',
                'resources/scss/light/assets/elements/tooltip.scss',
                
                
                // --- Forms
                'resources/scss/light/assets/forms/switches.scss',
                
                // --- Pages
                'resources/scss/light/assets/pages/contact_us.scss',
                'resources/scss/light/assets/pages/faq.scss',
                'resources/scss/light/assets/pages/knowledge_base.scss',
                'resources/scss/light/assets/pages/error/error.scss',
                'resources/scss/light/assets/pages/error/style-maintanence.scss',


                // --- Users
                'resources/scss/light/assets/users/account-setting.scss',
                'resources/scss/light/assets/users/user-profile.scss',
                

                // --- Widgets
                'resources/scss/light/assets/widgets/modules-widgets.scss',

                /**
                 * Dark
                 */

                // --- Apps
                'resources/scss/dark/assets/apps/blog-create.scss',
                'resources/scss/dark/assets/apps/blog-post.scss',
                'resources/scss/dark/assets/apps/chat.scss',
                'resources/scss/dark/assets/apps/contacts.scss',
                'resources/scss/dark/assets/apps/ecommerce-create.scss',
                'resources/scss/dark/assets/apps/ecommerce-details.scss',
                'resources/scss/dark/assets/apps/invoice-add.scss',
                'resources/scss/dark/assets/apps/invoice-edit.scss',
                'resources/scss/dark/assets/apps/invoice-list.scss',
                'resources/scss/dark/assets/apps/invoice-preview.scss',
                'resources/scss/dark/assets/apps/mailbox.scss',
                'resources/scss/dark/assets/apps/notes.scss',
                'resources/scss/dark/assets/apps/scrumboard.scss',
                'resources/scss/dark/assets/apps/todolist.scss',
                
                // --- Authentication
                'resources/scss/dark/assets/authentication/auth-boxed.scss',
                'resources/scss/dark/assets/authentication/auth-cover.scss',

                
                // --- Componenets
                'resources/scss/dark/assets/components/accordions.scss',
                'resources/scss/dark/assets/components/carousel.scss',
                'resources/scss/dark/assets/components/flags.scss',
                'resources/scss/dark/assets/components/font-icons.scss',
                'resources/scss/dark/assets/components/list-group.scss',
                'resources/scss/dark/assets/components/media_object.scss',
                'resources/scss/dark/assets/components/modal.scss',
                'resources/scss/dark/assets/components/tabs.scss',
                'resources/scss/dark/assets/components/timeline.scss',

                // --- Dashbaord
                'resources/scss/dark/assets/dashboard/dash_1.scss',
                'resources/scss/dark/assets/dashboard/dash_2.scss',
                
                // --- Elements
                'resources/scss/dark/assets/elements/alert.scss',
                'resources/scss/dark/assets/elements/color_library.scss',
                'resources/scss/dark/assets/elements/custom-pagination.scss',
                'resources/scss/dark/assets/elements/custom-tree_view.scss',
                'resources/scss/dark/assets/elements/custom-typography.scss',
                'resources/scss/dark/assets/elements/infobox.scss',
                'resources/scss/dark/assets/elements/popover.scss',
                'resources/scss/dark/assets/elements/search.scss',
                'resources/scss/dark/assets/elements/tooltip.scss',
                
                
                // --- Forms
                'resources/scss/dark/assets/forms/switches.scss',
                
                // --- Pages
                'resources/scss/dark/assets/pages/contact_us.scss',
                'resources/scss/dark/assets/pages/faq.scss',
                'resources/scss/dark/assets/pages/knowledge_base.scss',
                'resources/scss/dark/assets/pages/error/error.scss',
                'resources/scss/dark/assets/pages/error/style-maintanence.scss',


                // --- Users
                'resources/scss/dark/assets/users/account-setting.scss',
                'resources/scss/dark/assets/users/user-profile.scss',
                

                // --- Widgets
                'resources/scss/dark/assets/widgets/modules-widgets.scss',





                /**
                 * =======================
                 *      Assets JS Files
                 * =======================
                 */
                
                // Outer Files
                'resources/js/custom.js',
                'resources/js/scrollspyNav.js',
                
                // APPS
                'resources/js/apps/blog-create.js',
                'resources/js/apps/chat.js',
                'resources/js/apps/contact.js',
                'resources/js/apps/ecommerce-create.js',
                'resources/js/apps/ecommerce-details.js',
                'resources/js/apps/invoice-add.js',
                'resources/js/apps/invoice-edit.js',
                'resources/js/apps/invoice-list.js',
                'resources/js/apps/invoice-preview.js',
                'resources/js/apps/mailbox.js',
                'resources/js/apps/notes.js',
                'resources/js/apps/scrumboard.js',
                'resources/js/apps/todoList.js',

                // Auth
                'resources/js/authentication/auth-cover.js',
                'resources/js/authentication/form-2.js',
                'resources/js/authentication/2-Step-Verification.js',

                // Components
                'resources/js/components/notification/custom-snackbar.js',
                'resources/js/components/custom-carousel.js',

                // Dashboard
                'resources/js/dashboard/dash_1.js',
                'resources/js/dashboard/dash_2.js',
                
                
                // Elements
                'resources/js/elements/popovers.js',
                'resources/js/elements/custom-search.js',
                'resources/js/elements/tooltip.js',
                
                // Forms
                'resources/js/forms/bootstrap_validation/bs_validation_script.js',
                'resources/js/forms/custom-clipboard.js',
                
                // Pages
                'resources/js/pages/faq.js',
                'resources/js/pages/knowledge-base.js',
                
                // Users
                'resources/js/users/account-settings.js',
                
                // Widget
                'resources/js/widgets/modules-widgets.js',

                // 'resources/js/widgets/_wSix.js',
                // 'resources/js/widgets/_wChartThree.js',
                // 'resources/js/widgets/_wHybridOne.js',
                // 'resources/js/widgets/_wActivityFive.js',

                // 'resources/js/widgets/_wTwo.js',
                // 'resources/js/widgets/_wOne.js',
                // 'resources/js/widgets/_wChartOne.js',
                // 'resources/js/widgets/_wChartTwo.js',
                // 'resources/js/widgets/_wActivityFour.js',
                

                
                
                /**
                 * =======================
                 *      Plugins Files
                 * =======================
                 */

                // Importing All the Plugin Custom SCSS File ( plugins.min.scss contains all the custom SCSS/CSS. )
                // 'resources/scss/light/plugins/plugins.min.scss',

                /**
                 * Light
                 */
                
                'resources/scss/light/plugins/apex/custom-apexcharts.scss',
                'resources/scss/light/plugins/autocomplete/css/custom-autoComplete.scss',
                'resources/scss/light/plugins/bootstrap-range-Slider/bootstrap-slider.scss',
                'resources/scss/light/plugins/bootstrap-touchspin/custom-jquery.bootstrap-touchspin.min.scss',
                'resources/scss/light/plugins/clipboard/custom-clipboard.scss',
                'resources/scss/light/plugins/drag-and-drop/dragula/example.scss',
                'resources/scss/light/plugins/editors/markdown/simplemde.min.scss',
                'resources/scss/light/plugins/editors/quill/quill.snow.scss',
                'resources/scss/light/plugins/filepond/custom-filepond.scss',
                'resources/scss/light/plugins/flatpickr/custom-flatpickr.scss',
                'resources/scss/light/plugins/fullcalendar/custom-fullcalendar.scss',
                'resources/scss/light/plugins/loaders/custom-loader.scss',
                'resources/scss/light/plugins/notification/snackbar/custom-snackbar.scss',
                'resources/scss/light/plugins/noUiSlider/custom-nouiSlider.scss',
                'resources/scss/light/plugins/perfect-scrollbar/perfect-scrollbar.scss',
                'resources/scss/light/plugins/pricing-table/css/component.scss',
                'resources/scss/light/plugins/splide/custom-splide.min.scss',
                'resources/scss/light/plugins/stepper/custom-bsStepper.scss',
                'resources/scss/light/plugins/sweetalerts2/custom-sweetalert.scss',
                'resources/scss/light/plugins/table/datatable/dt-global_style.scss',
                'resources/scss/light/plugins/table/datatable/custom_dt_custom.scss',
                'resources/scss/light/plugins/table/datatable/custom_dt_miscellaneous.scss',
                'resources/scss/light/plugins/tagify/custom-tagify.scss',
                'resources/scss/light/plugins/tomSelect/custom-tomSelect.scss',

                /**
                 * Dark
                 */

                'resources/scss/dark/plugins/apex/custom-apexcharts.scss',
                'resources/scss/dark/plugins/autocomplete/css/custom-autoComplete.scss',
                'resources/scss/dark/plugins/bootstrap-range-Slider/bootstrap-slider.scss',
                'resources/scss/dark/plugins/bootstrap-touchspin/custom-jquery.bootstrap-touchspin.min.scss',
                'resources/scss/dark/plugins/clipboard/custom-clipboard.scss',
                'resources/scss/dark/plugins/drag-and-drop/dragula/example.scss',
                'resources/scss/dark/plugins/editors/markdown/simplemde.min.scss',
                'resources/scss/dark/plugins/editors/quill/quill.snow.scss',
                'resources/scss/dark/plugins/filepond/custom-filepond.scss',
                'resources/scss/dark/plugins/flatpickr/custom-flatpickr.scss',
                'resources/scss/dark/plugins/fullcalendar/custom-fullcalendar.scss',
                'resources/scss/dark/plugins/loaders/custom-loader.scss',
                'resources/scss/dark/plugins/notification/snackbar/custom-snackbar.scss',
                'resources/scss/dark/plugins/noUiSlider/custom-nouiSlider.scss',
                'resources/scss/dark/plugins/perfect-scrollbar/perfect-scrollbar.scss',
                'resources/scss/dark/plugins/pricing-table/css/component.scss',
                'resources/scss/dark/plugins/splide/custom-splide.min.scss',
                'resources/scss/dark/plugins/stepper/custom-bsStepper.scss',
                'resources/scss/dark/plugins/sweetalerts2/custom-sweetalert.scss',
                'resources/scss/dark/plugins/table/datatable/dt-global_style.scss',
                'resources/scss/dark/plugins/table/datatable/custom_dt_custom.scss',
                'resources/scss/dark/plugins/table/datatable/custom_dt_miscellaneous.scss',
                'resources/scss/dark/plugins/tagify/custom-tagify.scss',
                'resources/scss/dark/plugins/tomSelect/custom-tomSelect.scss',

                'resources/js/scrollspyNav.js',

                // 'resources/layouts/modern-light-menu/app.js',
                // 'resources/layouts/modern-dark-menu/app.js',
                // 'resources/layouts/collapsible-menu/app.js',
                // 'resources/layouts/horizontal-light-menu/app.js',
                // 'resources/layouts/horizontal-dark-menu/app.js',
                'resources/layouts/semi-dark-menu/app.js',
                // 'resources/layouts/vertical-dark-menu/app.js',
                // 'resources/layouts/vertical-light-menu/app.js',
                
                
                
                // 'resources/css/app.css',
                // 'resources/js/app.js'
            
            ],
            refresh: true,
        }),
    ],
});
