/**
 * JS 侧可用的设计变量镜像
 * 原因：SCSS 变量无法在 <script> 中引用（如 canvas 绘制、动态 style 计算），
 *       因此颜色等需要在 JS 中使用的 token 在此镜像一份。
 * 规则：修改 styles/variables.scss 时必须同步本文件。
 */
export const colorPrimaryVar = '#2B7CFF'
export const colorSuccessVar = '#22C55E'
export const colorDangerVar = '#EF4444'
export const colorWarningVar = '#F59E0B'
export const colorVipVar = '#F7B500'
export const colorTextPrimaryVar = '#1A1A1A'
export const colorTextSecondaryVar = '#8A94A6'

/** 图表配色（学习报告、成绩分析使用） */
export const chartColors = [colorPrimaryVar, colorSuccessVar, colorWarningVar, colorDangerVar]
