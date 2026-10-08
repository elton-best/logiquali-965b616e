<tr>
  <td class="header" style="padding: 28px 0 16px; text-align: center;">
    <a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
      <span
        style="
          display: inline-flex;
          align-items: center;
          gap: 10px;
          padding: 10px 16px;
          border-radius: 999px;
          background: linear-gradient(135deg, #0f4c81 0%, #0a75c2 100%);
          color: #ffffff;
          font-weight: 800;
          font-size: 14px;
          letter-spacing: 0.08em;
          text-transform: uppercase;
          box-shadow: 0 10px 24px rgba(15, 76, 129, 0.25);
        "
      >
        <span
          style="
            display: inline-grid;
            place-items: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            font-size: 11px;
            font-weight: 900;
          "
        >
          LQ
        </span>
        {{ env("APP_NAME") }}
      </span>
    </a>
  </td>
</tr>
