import {createRouter, createWebHistory} from "vue-router";
import OfficesPage from '../pages/OfficesPage.vue';
import DefaultLayout from '../layouts/DefaultLayout.vue';

const routes = [
    {
        path: '/',
        redirect: '/',
        component: DefaultLayout,
        children: [
            {path: '/', name: 'Offices', component: OfficesPage},
        ]
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router;