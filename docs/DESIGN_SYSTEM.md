# DESIGN SYSTEM

## Identidade Visual
O projeto usa **TailwindCSS 4**. As cores e estilos devem seguir uma abordagem moderna, corporativa, mas que "Wow" os usuários. 

## Cores (Sugestão baseada em Tailwind v4)
* **Primary**: Indigo ou Blue (ex: `blue-600` para primário).
* **Secondary**: Slate ou Gray para fundos, bordas e textos neutros.
* **Success**: Green (`green-500`) para status operacional, checklists em conformidade.
* **Warning**: Yellow/Orange (`orange-500`) para alertas e preventivas.
* **Danger**: Red (`red-600`) para veículos bloqueados, multas e falhas críticas.

## Tipografia
* **Fonte principal**: Inter, Roboto ou Outfit (sans-serif moderno).
* **Hierarquia**:
  * H1: text-3xl font-bold tracking-tight
  * H2: text-xl font-semibold
  * Body: text-sm ou text-base text-gray-700 dark:text-gray-300

## Componentes

### Botões
Devem ter feedback visual de hover e active.
* Primary: `bg-blue-600 hover:bg-blue-700 text-white rounded-md px-4 py-2 transition-all`
* Secondary: `bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-md px-4 py-2 transition-all`

### Cards e Modais
* Fundo branco ou `gray-800` (dark mode), com `shadow-sm`, bordas `border-gray-200`.

### Estados e Feedbacks
* **Loading**: Spinners SVG ou skeletons (pulse).
* **Empty States**: Ilustrações simples ou ícones indicando "Nenhum dado encontrado" com botão de ação.
* **Erros**: Banners ou Toasts vermelhos/alaranjados descrevendo a ação mitigadora.

## Dark Mode
Suporte nativo preparado através do tailwind `dark:` modifier, visando garantir conforto em ambientes operacionais escuros.
