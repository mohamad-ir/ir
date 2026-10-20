"""
Generates synthetic, clearly-labelled OCT B-scan and fundus illustrations used
only as placeholder demo content. They are not real patient images.

    python3 scripts/generate_demo_images.py database/seeders/assets
"""
import math
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont

W, H = 1400, 620
rng = np.random.default_rng(7)


def gauss(x, mu, sigma, amp):
    return amp * np.exp(-((x - mu) ** 2) / (2 * sigma ** 2))


def label(img, text="SYNTHETIC ILLUSTRATION — NOT PATIENT DATA"):
    d = ImageDraw.Draw(img)
    try:
        font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf", 15)
    except OSError:
        font = ImageFont.load_default()
    d.text((18, img.height - 30), text, fill=(150, 150, 150), font=font)
    return img


def bscan(name, *, srf=None, cysts=(), erm=False, hole=False, drusen=(), ped=None, shrm=None, thick=0.0, vmt=False, choroid=1.0, tilt=0.0):
    x = np.arange(W, dtype=float)
    cx = W / 2
    base = 210 + tilt * (x - cx) / W * 80 + 6 * np.sin(x / 260)
    pit = gauss(x, cx, 95, 58)
    swelling = gauss(x, cx, 260, thick)

    ilm = base + pit - swelling
    rpe = 430 + tilt * (x - cx) / W * 80 + 4 * np.sin(x / 300)

    for (mu, s, a) in drusen:
        rpe -= gauss(x, mu, s, a)
    if ped:
        mu, s, a = ped
        rpe_floor = rpe.copy()
        rpe -= gauss(x, mu, s, a)
    else:
        rpe_floor = rpe.copy()

    srf_h = np.zeros(W)
    if srf:
        mu, s, a = srf
        srf_h = gauss(x, mu, s, a)
    if shrm:
        mu, s, a = shrm
        srf_h += gauss(x, mu, s, a)

    ez = rpe - 14 - srf_h
    elm = ez - 10
    curve = 0.00005 * (x - cx) ** 2
    ilm = ilm - curve
    rpe = rpe - curve
    rpe_floor = rpe_floor - curve
    ez = ez - curve
    elm = elm - curve

    g = gauss(x, cx, 75, 0.96)
    retina_t = elm - ilm
    onl_t = np.maximum(26, retina_t * 0.40 + gauss(x, cx, 80, 16))
    opl_t = 7 * (1 - 0.5 * g)
    inl_t = retina_t * 0.15 * (1 - g)
    ipl_t = retina_t * 0.12 * (1 - g)
    gcl_t = retina_t * 0.15 * (1 - g)
    b5 = np.maximum(ilm, elm - onl_t)
    b4 = np.maximum(ilm, b5 - opl_t)
    b3 = np.maximum(ilm, b4 - inl_t)
    b2 = np.maximum(ilm, b3 - ipl_t)
    b1 = np.maximum(ilm, b2 - gcl_t)

    img = np.zeros((H, W), dtype=float)
    yy = np.arange(H, dtype=float)[:, None]

    def band(top, bottom, val):
        mask = (yy >= top[None, :]) & (yy < bottom[None, :])
        img[mask] = val

    vitreous = 0.02
    img[:] = vitreous
    band(ilm, b1, 0.74)
    band(b1, b2, 0.36)
    band(b2, b3, 0.52)
    band(b3, b4, 0.16)
    band(b4, b5, 0.56)
    band(b5, elm, 0.09)
    band(elm, elm + 2, 0.42)
    band(elm + 2, ez, 0.14)
    band(ez, ez + 5, 0.82)
    if srf is not None or shrm is not None:
        band(ez + 5, rpe - 8, 0.03)
        if shrm:
            mu, s, a = shrm
            sh = gauss(x, mu, s, a * 0.8) - curve * 0
            band(rpe - 8 - sh, rpe - 8, 0.62)
    else:
        band(ez + 5, rpe - 8, 0.3)
    band(rpe - 8, rpe + 6, 0.97)
    if ped:
        band(rpe + 6, rpe_floor, 0.18)
        band(rpe_floor, rpe_floor + 3, 0.6)
    chor_top = rpe_floor + 6
    depth = 150 * choroid
    for i in range(int(depth)):
        band(chor_top + i, chor_top + i + 1, 0.42 - 0.26 * (i / depth))
    band(chor_top + depth, np.full(W, H), 0.07)

    # choroidal vessels
    for _ in range(int(55 * choroid)):
        vx, vy = rng.uniform(0, W), rng.uniform(20, 130 * choroid)
        rx, ry = rng.uniform(10, 34), rng.uniform(5, 14)
        mask = ((np.arange(W)[None, :] - vx) / rx) ** 2 + ((yy - (np.interp(vx, x, chor_top) + vy)) / ry) ** 2 < 1
        img[mask] = 0.07

    # intraretinal cysts
    for (cxx, depth, rx, ry) in cysts:
        cy = np.interp(cxx, x, ilm) + depth
        mask = ((np.arange(W)[None, :] - cxx) / rx) ** 2 + ((yy - cy) / ry) ** 2 < 1
        img[mask] = 0.03
        ring = ((np.arange(W)[None, :] - cxx) / (rx + 2)) ** 2 + ((yy - cy) / (ry + 2)) ** 2 < 1
        img[ring & ~mask] = np.maximum(img[ring & ~mask], 0.5)

    if hole:
        width = gauss(yy.repeat(W, axis=1), 0, 1, 0)  # placeholder to keep shape
        for col in range(W):
            half = 34 + (np.interp(col, x, elm) - 0) * 0
        hw_top, hw_bottom = 70, 40
        for col in range(int(cx - hw_top - 20), int(cx + hw_top + 20)):
            top, bottom = ilm[col], elm[col] + 3
            for row in range(int(top) - 2, int(bottom)):
                frac = (row - top) / max(1, bottom - top)
                hw = hw_top + (hw_bottom - hw_top) * frac
                if abs(col - cx) < hw:
                    img[row, col] = vitreous
        # cystic edges
        for side in (-1, 1):
            for k in range(3):
                ccx = cx + side * (hw_top + 18 + k * 22)
                cy = np.interp(ccx, x, ilm) + 45 + k * 6
                mask = ((np.arange(W)[None, :] - ccx) / 12) ** 2 + ((yy - cy) / 18) ** 2 < 1
                img[mask] = 0.04
        # operculum
        mask = ((np.arange(W)[None, :] - cx) / 40) ** 2 + ((yy - (ilm.min() - 55)) / 6) ** 2 < 1
        img[mask] = 0.55

    if erm:
        line = ilm - 4 + 2.5 * np.sin(x / 9)
        on = (x > cx - 420) & (x < cx + 360)
        for col in np.where(on)[0]:
            r = int(line[col])
            img[r - 1:r + 2, col] = 0.95
        # wrinkling of inner layers
        img = np.roll(img, 0, axis=0)

    if vmt:
        hyaloid = ilm - 6 - gauss(x, cx, 160, 1) * 0
        for col in range(W):
            if abs(col - cx) > 60:
                r = int(ilm[col] - 18 - 0.00004 * (col - cx) ** 2 * 30)
                if 0 < r < H:
                    img[r:r + 2, col] = 0.6
        for col in range(int(cx - 60), int(cx + 60)):
            r0, r1 = int(ilm[col] - 22), int(ilm[col])
            img[r0:r1, col] = np.maximum(img[r0:r1, col], 0.45)

    # speckle and optics
    speckle = rng.gamma(2.2, 1 / 2.2, size=img.shape)
    img = img * speckle + rng.normal(0, 0.025, size=img.shape)
    img = np.clip(img, 0, 1) ** 0.85
    out = Image.fromarray((img * 255).astype(np.uint8), "L").filter(ImageFilter.GaussianBlur(0.9))
    out = out.resize((W, H), Image.BICUBIC).convert("RGB")
    return label(out)


def fundus(name, *, drusen=False, haemorrhages=False, exudates=False, serous=False, vasculitis=False):
    S = 900
    img = Image.new("RGB", (S, S), (0, 0, 0))
    arr = np.zeros((S, S, 3), dtype=float)
    yy, xx = np.mgrid[0:S, 0:S]
    r = np.hypot(xx - S / 2, yy - S / 2)
    mask = r < S * 0.47
    base = np.stack([0.78 - r / S * 0.55, 0.32 - r / S * 0.22, 0.13 - r / S * 0.08], axis=-1)
    arr[mask] = base[mask]
    # macula darker
    mac = np.exp(-((xx - S * 0.52) ** 2 + (yy - S * 0.5) ** 2) / (2 * 60 ** 2))
    arr[..., 0] -= mac * 0.18 * mask
    arr[..., 1] -= mac * 0.08 * mask
    # optic disc
    disc_c = (S * 0.25, S * 0.48)
    disc = np.hypot(xx - disc_c[0], yy - disc_c[1])
    arr[disc < 62] = [0.98, 0.82, 0.55]
    arr[disc < 26] = [1.0, 0.95, 0.82]
    noise = rng.normal(0, 0.025, size=arr.shape)
    arr = np.clip(arr + noise * mask[..., None], 0, 1)
    img = Image.fromarray((arr * 255).astype(np.uint8))
    d = ImageDraw.Draw(img)
    # vessels: arcades
    for sign in (-1, 1):
        for k, (w, col) in enumerate([(9, (120, 20, 15)), (6, (160, 40, 25))]):
            pts = []
            for t in np.linspace(0, 1, 60):
                x = disc_c[0] + t * S * 0.62
                y = disc_c[1] + sign * (math.sin(t * math.pi * 0.9) * S * (0.2 + 0.04 * k)) - sign * t * 40
                pts.append((x, y))
            d.line(pts, fill=col, width=w, joint="curve")
            for b in range(6):
                t0 = 0.2 + b * 0.13
                px, py = pts[int(t0 * 59)]
                d.line([(px, py), (px + rng.uniform(40, 120), py + sign * rng.uniform(40, 140))], fill=col, width=max(2, w - 4))
    if drusen:
        for _ in range(70):
            x, y = rng.normal(S * 0.53, 55), rng.normal(S * 0.5, 45)
            rr = rng.uniform(3, 9)
            d.ellipse([x - rr, y - rr, x + rr, y + rr], fill=(235, 205, 120))
    if exudates:
        for _ in range(40):
            x, y = rng.normal(S * 0.56, 70), rng.normal(S * 0.47, 50)
            rr = rng.uniform(2, 6)
            d.ellipse([x - rr, y - rr, x + rr, y + rr], fill=(250, 235, 150))
    if haemorrhages:
        for _ in range(60):
            x, y = rng.uniform(S * 0.2, S * 0.85), rng.uniform(S * 0.15, S * 0.85)
            if math.hypot(x - S / 2, y - S / 2) > S * 0.44:
                continue
            rr = rng.uniform(4, 14)
            d.ellipse([x - rr, y - rr * 0.6, x + rr, y + rr * 0.6], fill=(110, 10, 10))
    if serous:
        d.ellipse([S * 0.43, S * 0.41, S * 0.62, S * 0.6], outline=(220, 140, 100), width=3)
    if vasculitis:
        for _ in range(12):
            x, y = rng.uniform(S * 0.3, S * 0.8), rng.uniform(S * 0.2, S * 0.8)
            d.ellipse([x - 10, y - 10, x + 10, y + 10], outline=(240, 230, 210), width=4)
    img = img.filter(ImageFilter.GaussianBlur(1.6))
    return label(img)


def portrait_placeholder():
    S = 1000
    img = Image.new("RGB", (S, int(S * 1.25)), (18, 50, 79))
    d = ImageDraw.Draw(img)
    for i in range(40):
        y = 400 + i * 18
        d.arc([-200, y - 600, S + 200, y + 200], 200, 340, fill=(15 + i, 139 - i, 141 - i // 2), width=2)
    d.ellipse([S / 2 - 170, 260, S / 2 + 170, 600], fill=(30, 72, 105))
    d.rounded_rectangle([S / 2 - 320, 640, S / 2 + 320, 1300], radius=260, fill=(30, 72, 105))
    return label(img, "PLACEHOLDER — REPLACE WITH PROFESSIONAL PHOTO")


def main(out):
    out = Path(out)
    out.mkdir(parents=True, exist_ok=True)
    scans = {
        "oct-normal": dict(),
        "oct-csr": dict(srf=(W / 2, 150, 85)),
        "oct-csr-resolved": dict(srf=(W / 2, 120, 6)),
        "oct-dme": dict(thick=55, cysts=[(560, 90, 22, 26), (640, 75, 30, 34), (720, 70, 34, 40), (790, 80, 26, 30), (860, 95, 18, 22), (690, 140, 16, 14)], srf=(W / 2, 90, 18)),
        "oct-erm": dict(erm=True, thick=40, cysts=[(700, 95, 10, 12)]),
        "oct-macular-hole": dict(hole=True),
        "oct-drusen": dict(drusen=[(520, 26, 16), (600, 30, 22), (690, 24, 18), (770, 32, 26), (860, 22, 14)]),
        "oct-namd": dict(ped=(W / 2 + 40, 120, 70), srf=(W / 2 - 60, 140, 40), shrm=(W / 2 + 20, 60, 22)),
        "oct-rvo": dict(thick=80, cysts=[(600, 80, 28, 36), (700, 70, 40, 52), (800, 82, 30, 38), (650, 140, 18, 18), (760, 145, 20, 20)]),
        "oct-vmt": dict(vmt=True, thick=25, cysts=[(700, 70, 14, 16)]),
        "oct-uveitic-cme": dict(thick=70, cysts=[(620, 85, 24, 30), (700, 70, 36, 46), (785, 85, 24, 30), (700, 150, 22, 16)], choroid=1.4),
        "oct-pachychoroid": dict(choroid=1.9, drusen=[(760, 40, 8)], srf=(W / 2 + 120, 70, 22)),
    }
    for name, kw in scans.items():
        bscan(name, **kw).save(out / f"{name}.jpg", quality=86, optimize=True)
    fundus("fundus-normal").save(out / "fundus-normal.jpg", quality=86, optimize=True)
    fundus("fundus-amd", drusen=True).save(out / "fundus-amd.jpg", quality=86, optimize=True)
    fundus("fundus-dr", haemorrhages=True, exudates=True).save(out / "fundus-dr.jpg", quality=86, optimize=True)
    fundus("fundus-crvo", haemorrhages=True).save(out / "fundus-crvo.jpg", quality=86, optimize=True)
    fundus("fundus-csr", serous=True).save(out / "fundus-csr.jpg", quality=86, optimize=True)
    fundus("fundus-vasculitis", vasculitis=True).save(out / "fundus-vasculitis.jpg", quality=86, optimize=True)
    portrait_placeholder().save(out / "portrait-placeholder.jpg", quality=86, optimize=True)


if __name__ == "__main__":
    main(sys.argv[1] if len(sys.argv) > 1 else "database/seeders/assets")
