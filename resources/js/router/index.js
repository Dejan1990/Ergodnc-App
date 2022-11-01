import {createRouter, createWebHistory} from "vue-router";
import OfficesPage from '../pages/OfficesPage.vue';
import OfficePage from '../pages/OfficePage.vue';
import ProfilePage from '../pages/ProfilePage.vue';
import LoginPage from '../pages/LoginPage.vue';
import DefaultLayout from '../layouts/DefaultLayout.vue';

const routes = [
    {
        path: '/',
        redirect: '/',
        component: DefaultLayout,
        children: [
            {path: '/', name: 'Offices', component: OfficesPage},
            {path: '/office/:id', name: 'Office', component: OfficePage},
            {path: '/profile', name: 'Profile', component: ProfilePage},
            {path: '/login', name: 'Login', component: LoginPage}
        ]
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router;