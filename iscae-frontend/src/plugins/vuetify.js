import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import 'vuetify/styles'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import '@mdi/font/css/materialdesignicons.css'

export default createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi },
  },
  defaults: {
    VBtn: {
      style: 'text-transform: none; letter-spacing: 0;',
      fontWeight: '500',
    },
    VCard: {
      elevation: 0,
    },
    VTextField: {
      variant: 'outlined',
      density: 'comfortable',
    },
    VSelect: {
      variant: 'outlined',
      density: 'comfortable',
    },
    VTextarea: {
      variant: 'outlined',
    },
  },
  theme: {
    defaultTheme: 'iscaeLight',
    themes: {
      iscaeLight: {
        dark: false,
        colors: {
          // ── Couleurs principales ISCAE ──────────────────────────────
          primary:          '#0F2D5E',   // bleu marine académique
          accent:           '#2563EB',   // bleu action (boutons, liens actifs)
          secondary:        '#374151',   // gris anthracite
          success:          '#16A34A',   // vert succès
          warning:          '#D97706',   // orange avertissement
          error:            '#DC2626',   // rouge erreur
          info:             '#0EA5E9',   // bleu info

          // ── Surfaces ────────────────────────────────────────────────
          background:       '#F4F6FA',   // fond page
          surface:          '#FFFFFF',   // fond card
          'surface-variant':'#F9FAFB',   // fond card variante
          'surface-bright': '#FFFFFF',

          // ── On-colors ───────────────────────────────────────────────
          'on-primary':     '#FFFFFF',
          'on-accent':      '#FFFFFF',
          'on-secondary':   '#FFFFFF',
          'on-success':     '#FFFFFF',
          'on-warning':     '#FFFFFF',
          'on-error':       '#FFFFFF',
          'on-background':  '#111827',
          'on-surface':     '#111827',

          // ── Texte & bordures ────────────────────────────────────────
          'text-primary':   '#111827',
          'text-secondary': '#6B7280',
          'text-disabled':  '#9CA3AF',
          'border-default': '#E5E7EB',
          'border-light':   '#F3F4F6',

          // ── Sidebar ─────────────────────────────────────────────────
          'sidebar-bg':     '#0F2D5E',
          'sidebar-active': 'rgba(255,255,255,0.12)',

          // ── États KPI ───────────────────────────────────────────────
          'kpi-orange-bg':  '#FEF3C7',
          'kpi-blue-bg':    '#DBEAFE',
          'kpi-green-bg':   '#DCFCE7',
          'kpi-red-bg':     '#FEE2E2',
        },
        variables: {
          // ── Border radius ────────────────────────────────────────────
          'border-radius-root':     '8px',
          'border-radius-sm':       '6px',
          'border-radius-md':       '10px',
          'border-radius-lg':       '12px',
          'border-radius-xl':       '16px',

          // ── Ombres ──────────────────────────────────────────────────
          'shadow-sm':  '0 1px 2px rgba(0,0,0,0.05)',
          'shadow-md':  '0 4px 12px rgba(0,0,0,0.08)',
          'shadow-lg':  '0 8px 24px rgba(0,0,0,0.10)',
          'shadow-card':'0 1px 3px rgba(0,0,0,0.06)',

          // ── Typographie ─────────────────────────────────────────────
          'font-size-root': '14px',
          'line-height-root': '1.5',

          // ── Composants ──────────────────────────────────────────────
          'medium-emphasis-opacity': '0.65',
          'disabled-opacity':        '0.4',
          'hover-opacity':           '0.04',
          'focus-opacity':           '0.06',
          'selected-opacity':        '0.10',
          'activated-opacity':       '0.12',
          'dragged-opacity':         '0.08',
          'kbd-background-color':    '#F3F4F6',
          'kbd-color':               '#111827',
          'code-background-color':   '#F3F4F6',
        },
      },

      iscaeDark: {
        dark: true,
        colors: {
          // ── Couleurs principales ────────────────────────────────────
          primary:          '#1E3A6E',
          accent:           '#3B82F6',
          secondary:        '#4B5563',
          success:          '#16A34A',
          warning:          '#D97706',
          error:            '#DC2626',
          info:             '#0EA5E9',

          // ── Surfaces ────────────────────────────────────────────────
          background:       '#0A0E1A',
          surface:          '#111827',
          'surface-variant':'#1F2937',
          'surface-bright': '#1F2937',

          // ── On-colors ───────────────────────────────────────────────
          'on-primary':     '#FFFFFF',
          'on-accent':      '#FFFFFF',
          'on-secondary':   '#FFFFFF',
          'on-success':     '#FFFFFF',
          'on-warning':     '#FFFFFF',
          'on-error':       '#FFFFFF',
          'on-background':  '#F9FAFB',
          'on-surface':     '#F9FAFB',

          // ── Texte & bordures ────────────────────────────────────────
          'text-primary':   '#F9FAFB',
          'text-secondary': '#9CA3AF',
          'text-disabled':  '#6B7280',
          'border-default': 'rgba(255,255,255,0.08)',
          'border-light':   'rgba(255,255,255,0.04)',

          // ── Sidebar ─────────────────────────────────────────────────
          'sidebar-bg':     '#060A14',
          'sidebar-active': 'rgba(255,255,255,0.10)',

          // ── États KPI (dark) ─────────────────────────────────────────
          'kpi-orange-bg':  'rgba(217,119,6,0.15)',
          'kpi-blue-bg':    'rgba(37,99,235,0.15)',
          'kpi-green-bg':   'rgba(22,163,74,0.15)',
          'kpi-red-bg':     'rgba(220,38,38,0.15)',
        },
        variables: {
          'border-radius-root': '8px',
          'border-radius-sm':   '6px',
          'border-radius-md':   '10px',
          'border-radius-lg':   '12px',
          'border-radius-xl':   '16px',
          'shadow-sm':  '0 1px 2px rgba(0,0,0,0.3)',
          'shadow-md':  '0 4px 12px rgba(0,0,0,0.4)',
          'shadow-lg':  '0 8px 24px rgba(0,0,0,0.5)',
          'shadow-card':'0 1px 3px rgba(0,0,0,0.3)',
          'font-size-root': '14px',
          'medium-emphasis-opacity': '0.60',
          'disabled-opacity':        '0.38',
          'hover-opacity':           '0.06',
          'focus-opacity':           '0.08',
          'selected-opacity':        '0.12',
          'activated-opacity':       '0.14',
        },
      },
    },
  },
})
