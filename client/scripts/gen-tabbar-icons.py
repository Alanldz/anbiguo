"""生成 tabBar 图标（PNG）。

用法：
    python scripts/gen-tabbar-icons.py

说明：
- 用 PyMuPDF 直接绘制矢量图形导出 PNG，不依赖外部图标资源
- 输出目录：src/static/tabbar/，每个图标 81x81，含普通态与选中态
- 命名规范见 docs/02-代码命名规范.md §2.5
- PyMuPDF >= 1.25 的 Shape 绘图方法不接收 color/width，样式统一在 finish(...) 设置
- 依赖：pip install pymupdf
"""
import os
import pymupdf

OUT_DIR = os.path.join(os.path.dirname(os.path.dirname(__file__)), "src", "static", "tabbar")
SIZE = 81
NORMAL = (0x8A / 255, 0x94 / 255, 0xA6 / 255)
ACTIVE = (0x2B / 255, 0x7C / 255, 0xFF / 255)
STROKE = 5.0


def draw_home(shape):
    shape.draw_polyline([(14, 38), (40.5, 15), (67, 38)])
    shape.draw_polyline([(20, 34), (20, 66), (61, 66), (61, 34)])


def draw_bank(shape):
    for x, y in ((15, 15), (45, 15), (15, 45), (45, 45)):
        shape.draw_rect(pymupdf.Rect(x, y, x + 21, y + 21), radius=0.28)


def draw_ai(shape):
    shape.draw_rect(pymupdf.Rect(14, 14, 67, 67), radius=0.24)
    shape.draw_line((40.5, 27), (40.5, 54))
    shape.draw_line((27, 40.5), (54, 40.5))


def draw_exam(shape):
    shape.draw_rect(pymupdf.Rect(18, 13, 63, 68), radius=0.2)
    shape.draw_line((29, 42), (38, 51))
    shape.draw_line((38, 51), (53, 31))


def draw_mine(shape):
    shape.draw_circle((40.5, 29), 12)
    shape.draw_sector((40.5, 72), (61.5, 72), 180, fullSector=False)


ICONS = {
    "home": draw_home,
    "bank": draw_bank,
    "ai": draw_ai,
    "exam": draw_exam,
    "mine": draw_mine,
}


def main():
    os.makedirs(OUT_DIR, exist_ok=True)
    for name, drawer in ICONS.items():
        for suffix, color in (("", NORMAL), ("-active", ACTIVE)):
            doc = pymupdf.open()
            page = doc.new_page(width=SIZE, height=SIZE)
            shape = page.new_shape()
            drawer(shape)
            # closePath 必须显式设为 False，否则折线（如对勾）会被自动闭合成多边形
            shape.finish(color=color, width=STROKE, lineCap=1, lineJoin=1, closePath=False)
            shape.commit()
            pix = page.get_pixmap(dpi=72, alpha=True)
            pix.save(os.path.join(OUT_DIR, f"{name}{suffix}.png"))
            doc.close()
    print("OK", sorted(os.listdir(OUT_DIR)))


if __name__ == "__main__":
    main()
