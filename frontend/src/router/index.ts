import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '../stores/user'
import HomeView from '../views/HomeView.vue'
import AdminLayout from '../components/AdminLayout.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterView.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: { requiresAuth: true }
    },
    {
      path: '/packs',
      name: 'pack-selection',
      component: () => import('../views/PackSelectionView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/packs/:packId/open',
      name: 'pack-opening',
      component: () => import('../views/PackOpeningView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/collection',
      name: 'collection',
      component: () => import('../views/CollectionView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/lineups',
      name: 'lineups',
      component: () => import('../views/LineupsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/lineups/:id',
      name: 'lineup-editor',
      component: () => import('../views/LineupEditorView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/stats/upload',
      name: 'stats-upload',
      component: () => import('../views/StatsUploadView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/programs',
      name: 'Programs',
      component: () => import('../views/ProgramsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/programs/team-affinity',
      name: 'TeamAffinity',
      component: () => import('../views/TeamAffinityView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/programs/:id',
      name: 'ProgramDetail',
      component: () => import('../views/ProgramDetailView.vue'),
      meta: { requiresAuth: true }
    },
    // Admin routes
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('../views/admin/AdminDashboard.vue'),
        },
        {
          path: 'players',
          name: 'admin-players',
          component: () => import('../views/admin/players/PlayersIndex.vue'),
        },
        {
          path: 'players/create',
          name: 'admin-players-create',
          component: () => import('../views/admin/players/PlayerForm.vue'),
        },
        {
          path: 'players/:id/edit',
          name: 'admin-players-edit',
          component: () => import('../views/admin/players/PlayerForm.vue'),
        },
        {
          path: 'programs',
          name: 'admin-programs',
          component: () => import('../views/admin/programs/ProgramsIndex.vue'),
        },
        {
          path: 'programs/create',
          name: 'admin-programs-create',
          component: () => import('../views/admin/programs/ProgramForm.vue'),
        },
        {
          path: 'programs/:id/edit',
          name: 'admin-programs-edit',
          component: () => import('../views/admin/programs/ProgramForm.vue'),
        },
        {
          path: 'programs/team-affinity',
          name: 'admin-team-affinity',
          component: () => import('../views/admin/programs/TeamAffinityManagement.vue'),
        },
        {
          path: 'packs',
          name: 'admin-packs',
          component: () => import('../views/admin/packs/PacksIndex.vue'),
        },
        {
          path: 'packs/create',
          name: 'admin-packs-create',
          component: () => import('../views/admin/packs/PackForm.vue'),
        },
        {
          path: 'packs/:id/edit',
          name: 'admin-packs-edit',
          component: () => import('../views/admin/packs/PackForm.vue'),
        },
        {
          path: 'collections',
          name: 'admin-collections',
          component: () => import('../views/admin/collections/CollectionsIndex.vue'),
        },
        {
          path: 'collections/create',
          name: 'admin-collections-create',
          component: () => import('../views/admin/collections/CollectionForm.vue'),
        },
        {
          path: 'collections/:id/edit',
          name: 'admin-collections-edit',
          component: () => import('../views/admin/collections/CollectionForm.vue'),
        },
      ]
    },
  ],
})

// Navigation guard
router.beforeEach(async (to, from, next) => {
  const userStore = useUserStore()
  
  // Try to fetch user if we have a token but no user
  if (userStore.token && !userStore.user) {
    await userStore.fetchUser()
  }

  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)
  const requiresAdmin = to.matched.some(record => record.meta.requiresAdmin)

  if (requiresAuth && !userStore.isAuthenticated) {
    next('/login')
  } else if (requiresGuest && userStore.isAuthenticated) {
    next('/')
  } else if (requiresAdmin && !userStore.isAdmin) {
    // Redirect non-admin users trying to access admin routes
    next('/')
  } else {
    next()
  }
})

export default router
